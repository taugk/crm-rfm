<?php

namespace App\Services;

use App\Models\Customers;
use App\Models\RfmCalculationBatch;
use App\Models\RfmScore;
use App\Models\RfmSegmentHistory;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfmService
{
    /**
     * Entry point: jalankan seluruh pipeline RFM + K-Means.
     * Mengembalikan batch yang sudah completed beserta steps log.
     */
    public function calculate(int $userId, int $kClusters, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $startTime = microtime(true);
        $steps = [];   // Log tiap langkah untuk ditampilkan di UI

        $to   = $to   ?? Carbon::now();
        $from = $from ?? Carbon::now()->subYears(2);

        // --- Buat batch record ---
        $batch = RfmCalculationBatch::create([
            'triggered_by'     => $userId,
            'k_clusters'       => $kClusters,
            'max_iterations'   => 100,
            'actual_iterations'=> 0,
            'data_from'        => $from->toDateString(),
            'data_to'          => $to->toDateString(),
            'total_customers'  => 0,
            'status'           => 'running',
        ]);

        try {
            // ================================================================
            // STEP 1 — Ambil raw data transaksi per pelanggan
            // ================================================================
            $rawData    = collect(); 
            $scored     = collect();
            $normalized = collect();
            $normStats  = [];
            $clustered  = collect();
            $centroids  = [];
            $iterations = 0;
            $inertia    = 0.0;
            $clusterLabels = [];
            $savedCount = 0;

            $steps[] = $this->stepRawData($rawData, $from, $to);

            if ($rawData->isEmpty()) {
                throw new \RuntimeException('Tidak ada data transaksi dalam rentang tanggal yang dipilih.');
            }

            $batch->update(['total_customers' => $rawData->count()]);

            // ================================================================
            // STEP 2 — Hitung skor quintile R, F, M (1–5)
            // ================================================================
            $steps[] = $this->stepQuintile($rawData, $scored);

            // ================================================================
            // STEP 3 — Min-Max normalization untuk K-Means
            // ================================================================
            $steps[] = $this->stepNormalize($scored, $normalized, $normStats);

            // ================================================================
            // STEP 4 — K-Means clustering
            // ================================================================
            $steps[] = $this->stepKMeans($normalized, $kClusters, $clustered, $centroids, $iterations, $inertia);

            // ================================================================
            // STEP 5 — Auto-label segmen berdasarkan centroid
            // ================================================================
            $steps[] = $this->stepAutoLabel($centroids, $clusterLabels);

            // ================================================================
            // STEP 6 — Simpan ke database + update histori
            // ================================================================
            $steps[] = $this->stepPersist($batch, $rawData, $scored, $normalized, $clustered, $clusterLabels, $centroids, $savedCount);

            // --- Finalisasi batch ---
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $batch->update([
                'status'           => 'completed',
                'actual_iterations'=> $iterations,
                'inertia'          => $inertia,
                'final_centroids'  => $centroids,
                'cluster_labels'   => $clusterLabels,
                'duration_ms'      => $durationMs,
            ]);

            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => true];

        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('RFM calculation failed', ['error' => $e->getMessage(), 'batch_id' => $batch->id]);
            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // STEP 1 — Raw data
    // =========================================================================
    private function stepRawData(&$rawData, Carbon $from, Carbon $to): array
    {
        $referenceDate = $to;

        $rawData = DB::table('transactions as t')
            ->join('customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.status', 'completed')
            ->whereBetween('t.transaction_date', [$from, $to])
            ->whereNull('c.deleted_at')
            ->groupBy('t.customer_id', 'c.name', 'c.email')
            ->selectRaw("
                t.customer_id,
                c.name,
                c.email,
                DATEDIFF(?, MAX(t.transaction_date)) AS recency_days,
                COUNT(t.id)                          AS frequency,
                SUM(t.total_price)                   AS monetary,
                MAX(t.transaction_date)              AS last_transaction_date,
                MIN(t.transaction_date)              AS first_transaction_date
            ", [$referenceDate])
            ->orderBy('t.customer_id')
            ->get();

        $stats = [
            'count'               => $rawData->count(),
            'avg_recency_days'    => round($rawData->avg('recency_days'), 1),
            'avg_frequency'       => round($rawData->avg('frequency'), 1),
            'avg_monetary'        => round($rawData->avg('monetary'), 2),
            'max_monetary'        => $rawData->max('monetary'),
            'min_recency_days'    => $rawData->min('recency_days'),
        ];

        return [
            'step'        => 1,
            'title'       => 'Pengambilan data transaksi',
            'description' => "Mengambil data {$stats['count']} pelanggan dengan transaksi completed dari {$from->format('d M Y')} hingga {$to->format('d M Y')}.",
            'stats'       => $stats,
            'formula'     => [
                'Recency'   => "DATEDIFF(tanggal_referensi, MAX(transaction_date))",
                'Frequency' => "COUNT(transaction_id) per customer",
                'Monetary'  => "SUM(total_price) per customer",
            ],
        ];
    }

    // =========================================================================
    // STEP 2 — Quintile scoring (1–5)
    // =========================================================================
    private function stepQuintile($rawData, &$scored): array
    {
        $recencies  = $rawData->pluck('recency_days')->sort()->values();
        $frequencies = $rawData->pluck('frequency')->sort()->values();
        $monetaries  = $rawData->pluck('monetary')->sort()->values();

        $scored = $rawData->map(function ($row) use ($recencies, $frequencies, $monetaries) {
            // Recency: semakin kecil (baru) = skor lebih tinggi → dibalik
            $rScore = $this->quintile($row->recency_days, $recencies, reverse: true);
            $fScore = $this->quintile($row->frequency, $frequencies, reverse: false);
            $mScore = $this->quintile($row->monetary, $monetaries, reverse: false);

            return (object) array_merge((array) $row, [
                'r_score'   => $rScore,
                'f_score'   => $fScore,
                'm_score'   => $mScore,
                'rfm_score' => round(($rScore + $fScore + $mScore) / 3, 2),
                'rfm_label' => "{$rScore}{$fScore}{$mScore}",
            ]);
        });

        // Hitung breakpoints quintile untuk ditampilkan
        $rBreaks = $this->quintileBreaks($recencies);
        $fBreaks = $this->quintileBreaks($frequencies);
        $mBreaks = $this->quintileBreaks($monetaries);

        return [
            'step'        => 2,
            'title'       => 'Scoring quintile R, F, M (skala 1–5)',
            'description' => 'Setiap metrik dibagi menjadi 5 kelompok sama besar (quintile). Recency dibalik: semakin baru = skor lebih tinggi.',
            'formula'     => [
                'Quintile' => 'Urutkan nilai → bagi menjadi 5 kelompok 20% → assign skor 1–5',
                'Recency'  => 'Skor 5 = customer paling baru belanja (recency_days paling kecil)',
                'Frequency'=> 'Skor 5 = customer paling sering belanja',
                'Monetary' => 'Skor 5 = customer dengan total belanja terbesar',
            ],
            'breakpoints' => [
                'recency'   => $rBreaks,
                'frequency' => $fBreaks,
                'monetary'  => $mBreaks,
            ],
            'distribution' => [
                'r' => $this->scoreDistribution($scored, 'r_score'),
                'f' => $this->scoreDistribution($scored, 'f_score'),
                'm' => $this->scoreDistribution($scored, 'm_score'),
            ],
        ];
    }

    /**
     * Hitung posisi quintile nilai dalam distribusi data.
     * reverse=true: nilai kecil → skor tinggi (untuk Recency)
     */
    private function quintile($value, $sorted, bool $reverse): int
    {
        $n = $sorted->count();
        if ($n === 0) return 3;

        $rank = $sorted->filter(fn($v) => $v <= $value)->count();
        $percentile = $rank / $n;

        $score = match(true) {
            $percentile <= 0.20 => 1,
            $percentile <= 0.40 => 2,
            $percentile <= 0.60 => 3,
            $percentile <= 0.80 => 4,
            default             => 5,
        };

        return $reverse ? (6 - $score) : $score;
    }

    private function quintileBreaks($sorted): array
    {
        $n = $sorted->count();
        return [
            'p20' => $sorted->get((int) ($n * 0.2)),
            'p40' => $sorted->get((int) ($n * 0.4)),
            'p60' => $sorted->get((int) ($n * 0.6)),
            'p80' => $sorted->get((int) ($n * 0.8)),
        ];
    }

    private function scoreDistribution($scored, string $field): array
    {
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($scored as $row) {
            $dist[$row->$field] = ($dist[$row->$field] ?? 0) + 1;
        }
        return $dist;
    }

    // =========================================================================
    // STEP 3 — Min-Max normalization
    // =========================================================================
    private function stepNormalize($scored, &$normalized, &$normStats): array
    {
        $rValues = $scored->pluck('recency_days');
        $fValues = $scored->pluck('frequency');
        $mValues = $scored->pluck('monetary');

        $normStats = [
            'recency'   => ['min' => $rValues->min(), 'max' => $rValues->max()],
            'frequency' => ['min' => $fValues->min(), 'max' => $fValues->max()],
            'monetary'  => ['min' => $mValues->min(), 'max' => $mValues->max()],
        ];

        $normalized = $scored->map(function ($row) use ($normStats) {
            return (object) array_merge((array) $row, [
                'recency_norm'   => $this->minMax($row->recency_days, $normStats['recency']['min'], $normStats['recency']['max'], reverse: true),
                'frequency_norm' => $this->minMax($row->frequency,    $normStats['frequency']['min'], $normStats['frequency']['max']),
                'monetary_norm'  => $this->minMax($row->monetary,     $normStats['monetary']['min'], $normStats['monetary']['max']),
            ]);
        });

        return [
            'step'        => 3,
            'title'       => 'Normalisasi Min-Max (0–1)',
            'description' => 'Nilai R, F, M dinormalisasi ke skala 0–1 agar K-Means tidak bias terhadap unit yang berbeda (hari vs transaksi vs rupiah). Recency tetap dibalik.',
            'formula'     => [
                'Min-Max'  => 'x_norm = (x − min) / (max − min)',
                'Recency'  => 'r_norm = 1 − (recency_days − min) / (max − min)   ← dibalik: makin baru = makin besar',
                'Frequency'=> 'f_norm = (frequency − min) / (max − min)',
                'Monetary' => 'm_norm = (monetary − min) / (max − min)',
            ],
            'stats' => $normStats,
        ];
    }

    private function minMax($value, $min, $max, bool $reverse = false): float
    {
        if ($max == $min) return 0.5;
        $norm = ($value - $min) / ($max - $min);
        return round($reverse ? (1 - $norm) : $norm, 6);
    }

    // =========================================================================
    // STEP 4 — K-Means clustering
    // =========================================================================
    private function stepKMeans($normalized, int $k, &$clustered, &$centroids, &$iterations, &$inertia): array
    {
        $maxIter = 100;
        $data    = $normalized->values()->toArray();
        $n       = count($data);

        // Inisialisasi centroid: K-Means++ untuk konvergensi lebih cepat
        $centroids = $this->initCentroidsKMeansPlusPlus($data, $k);

        $assignments = array_fill(0, $n, 0);
        $iterLog     = [];

        for ($iter = 0; $iter < $maxIter; $iter++) {
            // Assignment step
            $newAssignments = [];
            foreach ($data as $i => $point) {
                $newAssignments[$i] = $this->nearestCentroid($point, $centroids);
            }

            // Hitung SSE iterasi ini
            $sse = $this->calculateSSE($data, $newAssignments, $centroids);
            $iterLog[] = ['iteration' => $iter + 1, 'sse' => round($sse, 6)];

            // Cek konvergensi
            if ($newAssignments === $assignments && $iter > 0) {
                $assignments = $newAssignments;
                $iterations  = $iter + 1;
                break;
            }
            $assignments = $newAssignments;

            // Update step: hitung ulang centroid
            $centroids = $this->updateCentroids($data, $assignments, $k);

            if ($iter === $maxIter - 1) {
                $iterations = $maxIter;
            }
        }

        $inertia = $this->calculateSSE($data, $assignments, $centroids);

        // Gabungkan assignment ke normalized data
        $clustered = $normalized->map(function ($row, $i) use ($assignments, $centroids) {
            $clusterId = $assignments[$i];
            $centroid  = $centroids[$clusterId];
            $distance  = $this->euclidean(
                [$row->recency_norm, $row->frequency_norm, $row->monetary_norm],
                $centroid
            );

            return (object) array_merge((array) $row, [
                'cluster_id'            => $clusterId,
                'distance_to_centroid'  => round($distance, 6),
            ]);
        });

        return [
            'step'        => 4,
            'title'       => "K-Means clustering (K={$k})",
            'description' => "Data ternormalisasi dikelompokkan menggunakan K-Means dengan inisialisasi K-Means++. Konvergen setelah {$iterations} iterasi.",
            'formula'     => [
                'Init'       => 'K-Means++: pilih centroid awal dengan probabilitas proporsional terhadap jarak kuadrat',
                'Assignment' => 'Tiap titik → cluster dengan centroid terdekat (Euclidean distance)',
                'Update'     => 'Centroid baru = rata-rata koordinat semua titik dalam cluster',
                'Konvergen'  => 'Hentikan jika assignment tidak berubah atau sudah 100 iterasi',
                'SSE/Inertia'=> 'Σ ||x_i − centroid_k||² — ukuran kompaknya cluster',
            ],
            'k'               => $k,
            'iterations'      => $iterations,
            'inertia'         => round($inertia, 6),
            'iter_log'        => $iterLog,
            'final_centroids' => array_map(fn($c) => array_map(fn($v) => round($v, 4), $c), $centroids),
            'cluster_sizes'   => $this->clusterSizes($assignments, $k),
        ];
    }

    private function initCentroidsKMeansPlusPlus(array $data, int $k): array
    {
        $n = count($data);
        $centroids = [];

        // Centroid pertama: acak
        $centroids[] = [$data[array_rand($data)]['recency_norm'], $data[array_rand($data)]['frequency_norm'], $data[array_rand($data)]['monetary_norm']];
        // Lebih tepat:
        $first = $data[array_rand($data)];
        $centroids = [[$first['recency_norm'], $first['frequency_norm'], $first['monetary_norm']]];

        for ($c = 1; $c < $k; $c++) {
            $distances = [];
            foreach ($data as $point) {
                $minDist = PHP_FLOAT_MAX;
                foreach ($centroids as $centroid) {
                    $d = $this->euclidean([$point['recency_norm'], $point['frequency_norm'], $point['monetary_norm']], $centroid);
                    $minDist = min($minDist, $d * $d);
                }
                $distances[] = $minDist;
            }
            // Pilih titik berikutnya dengan probabilitas proporsional terhadap jarak kuadrat
            $total = array_sum($distances);
            $rand  = mt_rand() / mt_getrandmax() * $total;
            $cumul = 0;
            $chosen = count($data) - 1;
            foreach ($distances as $i => $d) {
                $cumul += $d;
                if ($cumul >= $rand) { $chosen = $i; break; }
            }
            $p = $data[$chosen];
            $centroids[] = [$p['recency_norm'], $p['frequency_norm'], $p['monetary_norm']];
        }

        return $centroids;
    }

    private function nearestCentroid(object $point, array $centroids): int
    {
        $minDist  = PHP_FLOAT_MAX;
        $nearest  = 0;
        $coords   = [$point->recency_norm, $point->frequency_norm, $point->monetary_norm];
        foreach ($centroids as $i => $centroid) {
            $d = $this->euclidean($coords, $centroid);
            if ($d < $minDist) { $minDist = $d; $nearest = $i; }
        }
        return $nearest;
    }

    private function updateCentroids(array $data, array $assignments, int $k): array
    {
        $sums  = array_fill(0, $k, [0.0, 0.0, 0.0]);
        $counts = array_fill(0, $k, 0);
        foreach ($data as $i => $point) {
            $c = $assignments[$i];
            $sums[$c][0] += $point['recency_norm'] ?? 0;
            $sums[$c][1] += $point['frequency_norm'] ?? 0;
            $sums[$c][2] += $point['monetary_norm'] ?? 0;
            $counts[$c]++;
        }
        return array_map(function ($sum, $count) {
            if ($count === 0) return [0.5, 0.5, 0.5]; // fallback
            return [$sum[0] / $count, $sum[1] / $count, $sum[2] / $count];
        }, $sums, $counts);
    }

    private function euclidean(array $a, array $b): float
    {
        return sqrt(($a[0]-$b[0])**2 + ($a[1]-$b[1])**2 + ($a[2]-$b[2])**2);
    }

    private function calculateSSE(array $data, array $assignments, array $centroids): float
    {
        $sse = 0;
        foreach ($data as $i => $point) {
            $c = $assignments[$i];
            $sse += ($point['recency_norm'] - $centroids[$c][0])**2
                  + ($point['frequency_norm'] - $centroids[$c][1])**2
                  + ($point['monetary_norm'] - $centroids[$c][2])**2;
        }
        return $sse;
    }

    private function clusterSizes(array $assignments, int $k): array
    {
        $sizes = array_fill(0, $k, 0);
        foreach ($assignments as $c) { $sizes[$c]++; }
        return $sizes;
    }

    // =========================================================================
    // STEP 5 — Auto-label segmen berdasarkan posisi centroid
    // =========================================================================
    private function stepAutoLabel(array $centroids, &$clusterLabels): array
    {
        // Hitung "RFM score" tiap centroid: rata-rata R, F, M (semua skala 0–1, sudah dibalik untuk R)
        $centroidScores = array_map(fn($c) => ($c[0] + $c[1] + $c[2]) / 3, $centroids);

        // Kandidat label berdasarkan skor centroid (dari tinggi ke rendah)
        $labelPool = ['Champions', 'Loyal Customers', 'Potential Loyalists',
                      'At Risk', 'Needs Attention', 'About to Sleep',
                      'Lost Customers', 'New Customers', 'Hibernating', 'Promising'];

        // Urutkan cluster dari centroid tertinggi → terendah, assign label
        arsort($centroidScores);
        $clusterLabels = [];
        $labelIdx = 0;
        foreach ($centroidScores as $clusterId => $score) {
            $clusterLabels[(string) $clusterId] = $labelPool[$labelIdx] ?? "Cluster {$clusterId}";
            $labelIdx++;
        }
        ksort($clusterLabels);

        return [
            'step'        => 5,
            'title'       => 'Auto-labeling segmen',
            'description' => 'Cluster diurutkan berdasarkan rata-rata nilai centroid (R+F+M). Cluster dengan centroid tertinggi = segmen terbaik (Champions). Label dapat diedit manual oleh admin.',
            'formula'     => [
                'Skor centroid' => '(recency_norm + frequency_norm + monetary_norm) / 3',
                'Ranking'       => 'Cluster → sort descending by centroid score → assign label dari pool',
            ],
            'centroid_scores' => array_map(fn($s) => round($s, 4), $centroidScores),
            'labels'          => $clusterLabels,
        ];
    }

    // =========================================================================
    // STEP 6 — Persist ke DB + histori
    // =========================================================================
    private function stepPersist($batch, $rawData, $scored, $normalized, $clustered, array $clusterLabels, array $centroids, &$savedCount): array
    {
        $savedCount    = 0;
        $changedCount  = 0;
        $clustered     = $clustered->keyBy('customer_id');

        DB::transaction(function () use ($batch, $rawData, $scored, $normalized, $clustered, $clusterLabels, $centroids, &$savedCount, &$changedCount) {
            foreach ($rawData as $raw) {
                $cid      = $raw->customer_id;
                $sc       = $scored->firstWhere('customer_id', $cid);
                $cl       = $clustered[$cid] ?? null;

                if (!$sc || !$cl) continue;

                $segmentName = $clusterLabels[(string) ($cl->cluster_id ?? 0)] ?? 'Unknown';

                // Simpan rfm_scores
                $rfmScore = RfmScore::create([
                    'customer_id'           => $cid,
                    'recency_days'          => $raw->recency_days,
                    'frequency'             => $raw->frequency,
                    'monetary'              => $raw->monetary,
                    'recency_norm'          => $cl->recency_norm,
                    'frequency_norm'        => $cl->frequency_norm,
                    'monetary_norm'         => $cl->monetary_norm,
                    'r_score'               => $sc->r_score,
                    'f_score'               => $sc->f_score,
                    'm_score'               => $sc->m_score,
                    'rfm_score'             => $sc->rfm_score,
                    'rfm_label'             => $sc->rfm_label,
                    'cluster_id'            => $cl->cluster_id,
                    'segment_name'          => $segmentName,
                    'distance_to_centroid'  => $cl->distance_to_centroid,
                    'calculation_batch_id'  => $batch->id,
                ]);

                // Cek histori terakhir
                $lastHistory = RfmSegmentHistory::where('customer_id', $cid)
                    ->latest()
                    ->first();

                $prevSegment  = $lastHistory?->segment_to;
                $isChanged    = $prevSegment !== $segmentName;
                if ($isChanged && $prevSegment !== null) $changedCount++;

                // Simpan rfm_segment_history
                RfmSegmentHistory::create([
                    'customer_id'          => $cid,
                    'rfm_score_id'         => $rfmScore->id,
                    'calculation_batch_id' => $batch->id,
                    'segment_from'         => $prevSegment,
                    'segment_to'           => $segmentName,
                    'recency_days'         => $raw->recency_days,
                    'frequency'            => $raw->frequency,
                    'monetary'             => $raw->monetary,
                    'rfm_score'            => $sc->rfm_score,
                    'is_segment_changed'   => $isChanged,
                ]);

                $savedCount++;
            }
        });

        return [
            'step'        => 6,
            'title'       => 'Menyimpan hasil ke database',
            'description' => "Berhasil menyimpan {$savedCount} skor RFM. {$changedCount} pelanggan berpindah segmen dibanding kalkulasi sebelumnya.",
            'stats'       => [
                'saved'   => $savedCount,
                'changed' => $changedCount,
            ],
        ];
    }


    /**
 * Menghitung SSE untuk nilai K tertentu tanpa melakukan full pipeline.
 * Digunakan untuk Elbow Method API.
 */
public function calculateSseForK(array $data, int $k): float
{
    // Konversi array ke object jika perlu (karena logic KMeans kamu pakai object)
    $mappedData = array_map(fn($item) => (object)$item, $data);
    
    // Inisialisasi
    $centroids = $this->initCentroidsKMeansPlusPlus($data, $k);
    $assignments = [];
    $maxIter = 20; // Cukup 20 iterasi untuk elbow agar cepat

    for ($iter = 0; $iter < $maxIter; $iter++) {
        $newAssignments = [];
        foreach ($mappedData as $i => $point) {
            $newAssignments[$i] = $this->nearestCentroid($point, $centroids);
        }

        if ($newAssignments === $assignments) break;
        $assignments = $newAssignments;
        $centroids = $this->updateCentroids($data, $assignments, $k);
    }

    return $this->calculateSSE($data, $assignments, $centroids);
}
}