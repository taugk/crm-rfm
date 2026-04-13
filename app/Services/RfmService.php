<?php

namespace App\Services;

use App\Models\RfmCalculationBatch;
use App\Models\RfmScore;
use App\Models\RfmSegmentHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfmService
{

    // =========================================================================
    // Helper: unified logger ke channel 'rfm'
    // =========================================================================

    /**
     * Tulis debug log ke channel 'rfm'.
     * Format: [RFM][batch:{batchId}][step:{step}] message | context
     */
    private function dbg(string $step, string $message, array $context = [], ?int $batchId = null): void
    {
        $prefix = '[RFM]';
        if ($batchId !== null) $prefix .= "[batch:{$batchId}]";
        $prefix .= "[{$step}]";

        Log::channel('rfm')->debug("{$prefix} {$message}", $context);
    }

    private function info(string $step, string $message, array $context = [], ?int $batchId = null): void
    {
        $prefix = '[RFM]';
        if ($batchId !== null) $prefix .= "[batch:{$batchId}]";
        $prefix .= "[{$step}]";

        Log::channel('rfm')->info("{$prefix} {$message}", $context);
    }

    private function warn(string $step, string $message, array $context = [], ?int $batchId = null): void
    {
        $prefix = '[RFM]';
        if ($batchId !== null) $prefix .= "[batch:{$batchId}]";
        $prefix .= "[{$step}]";

        Log::channel('rfm')->warning("{$prefix} {$message}", $context);
    }

    private function err(string $step, string $message, array $context = [], ?int $batchId = null): void
    {
        $prefix = '[RFM]';
        if ($batchId !== null) $prefix .= "[batch:{$batchId}]";
        $prefix .= "[{$step}]";

        Log::channel('rfm')->error("{$prefix} {$message}", $context);
    }

    /** Kembalikan microtime float */
    private function timer(): float
    {
        return microtime(true);
    }

    /** Format durasi dalam ms */
    private function elapsed(float $start): string
    {
        return round((microtime(true) - $start) * 1000, 2) . ' ms';
    }


    // =========================================================================
    // ENTRY POINT
    // =========================================================================

    public function calculate(int $userId, int $kClusters, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $startTime = microtime(true);
        $steps = [];

        $to   = $to   ?? Carbon::now();
        $from = $from ?? Carbon::now()->subYears(2);

        // -------------------------------------------------------------------
        Log::channel('rfm')->info('[RFM][calculate] ===== KALKULASI DIMULAI =====', [
            'user_id'    => $userId,
            'k_clusters' => $kClusters,
            'from'       => $from->toDateString(),
            'to'         => $to->toDateString(),
        ]);
        // -------------------------------------------------------------------

        // --- Buat batch record ---
        $t = $this->timer();
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

        $this->info('init', 'Batch record dibuat', [
            'batch_id'   => $batch->id,
            'elapsed'    => $this->elapsed($t),
        ], $batch->id);

        try {
            // ================================================================
            // STEP 1
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

            $t1 = $this->timer();
            $this->dbg('step1', 'Mulai pengambilan raw data', [], $batch->id);

            $steps[] = $this->stepRawData($rawData, $from, $to);

            $this->info('step1', 'Raw data selesai diambil', [
                'total_rows' => $rawData->count(),
                'elapsed'    => $this->elapsed($t1),
            ], $batch->id);

            if ($rawData->isEmpty()) {
                $this->err('step1', 'Tidak ada data transaksi — pipeline dihentikan', [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ], $batch->id);
                throw new \RuntimeException('Tidak ada data transaksi dalam rentang tanggal yang dipilih.');
            }

            // Log sampel 3 baris raw data (hindari dump besar di production)
            $this->dbg('step1', 'Sampel raw data (maks 3 baris)', [
                'sample' => $rawData->take(3)->toArray(),
            ], $batch->id);

            $batch->update(['total_customers' => $rawData->count()]);

            // ================================================================
            // STEP 2
            // ================================================================
            $t2 = $this->timer();
            $this->dbg('step2', 'Mulai scoring quintile', [], $batch->id);

            $steps[] = $this->stepQuintile($rawData, $scored);

            $this->info('step2', 'Quintile scoring selesai', [
                'total_scored' => $scored->count(),
                'elapsed'      => $this->elapsed($t2),
                'distribution' => [
                    'r' => $this->scoreDistribution($scored, 'r_score'),
                    'f' => $this->scoreDistribution($scored, 'f_score'),
                    'm' => $this->scoreDistribution($scored, 'm_score'),
                ],
            ], $batch->id);

            $this->dbg('step2', 'Sampel scored data (maks 3 baris)', [
                'sample' => $scored->take(3)->map(fn($r) => [
                    'customer_id' => $r->customer_id,
                    'r_score'     => $r->r_score,
                    'f_score'     => $r->f_score,
                    'm_score'     => $r->m_score,
                    'rfm_score'   => $r->rfm_score,
                ])->values()->toArray(),
            ], $batch->id);

            // ================================================================
            // STEP 3
            // ================================================================
            $t3 = $this->timer();
            $this->dbg('step3', 'Mulai normalisasi Min-Max', [], $batch->id);

            $steps[] = $this->stepNormalize($scored, $normalized, $normStats);

            $this->info('step3', 'Normalisasi selesai', [
                'norm_stats' => $normStats,
                'elapsed'    => $this->elapsed($t3),
            ], $batch->id);

            $this->dbg('step3', 'Sampel normalized data (maks 3 baris)', [
                'sample' => $normalized->take(3)->map(fn($r) => [
                    'customer_id'    => $r->customer_id,
                    'recency_norm'   => $r->recency_norm,
                    'frequency_norm' => $r->frequency_norm,
                    'monetary_norm'  => $r->monetary_norm,
                ])->values()->toArray(),
            ], $batch->id);

            // ================================================================
            // STEP 4
            // ================================================================
            $t4 = $this->timer();
            $this->dbg('step4', "Mulai K-Means (K={$kClusters})", [], $batch->id);

            $steps[] = $this->stepKMeans($normalized, $kClusters, $clustered, $centroids, $iterations, $inertia);

            $this->info('step4', 'K-Means selesai', [
                'k'          => $kClusters,
                'iterations' => $iterations,
                'inertia'    => round($inertia, 6),
                'elapsed'    => $this->elapsed($t4),
                'cluster_sizes' => $this->clusterSizes(
                    $clustered->pluck('cluster_id')->toArray(),
                    $kClusters
                ),
            ], $batch->id);

            $this->dbg('step4', 'Final centroids setelah konvergensi', [
                'centroids' => array_map(fn($c) => array_map(fn($v) => round($v, 6), $c), $centroids),
            ], $batch->id);

            // ================================================================
            // STEP 5
            // ================================================================
            $t5 = $this->timer();
            $this->dbg('step5', 'Mulai auto-labeling segmen', [], $batch->id);

            $steps[] = $this->stepAutoLabel($centroids, $clusterLabels);

            $this->info('step5', 'Auto-labeling selesai', [
                'cluster_labels' => $clusterLabels,
                'elapsed'        => $this->elapsed($t5),
            ], $batch->id);

            // ================================================================
            // STEP 6
            // ================================================================
            $t6 = $this->timer();
            $this->dbg('step6', 'Mulai persist ke database', [], $batch->id);

            $steps[] = $this->stepPersist($batch, $rawData, $scored, $normalized, $clustered, $clusterLabels, $centroids, $savedCount);

            $this->info('step6', 'Persist selesai', [
                'saved_count' => $savedCount,
                'elapsed'     => $this->elapsed($t6),
            ], $batch->id);

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

            Log::channel('rfm')->info('[RFM][calculate] ===== KALKULASI SELESAI =====', [
                'batch_id'    => $batch->id,
                'status'      => 'completed',
                'duration_ms' => $durationMs,
                'saved'       => $savedCount,
                'k'           => $kClusters,
                'iterations'  => $iterations,
                'inertia'     => round($inertia, 6),
            ]);

            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => true];

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            $this->err('calculate', 'Pipeline gagal — exception ditangkap', [
                'batch_id'    => $batch->id,
                'error'       => $e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'duration_ms' => $durationMs,
                'trace'       => collect(explode("\n", $e->getTraceAsString()))->take(10)->toArray(),
            ], $batch->id);

            Log::error('RFM calculation failed', ['error' => $e->getMessage(), 'batch_id' => $batch->id]);

            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // STEP 1 — Raw data
    // =========================================================================
    private function stepRawData(&$rawData, Carbon $from, Carbon $to): array
    {
        $t = $this->timer();
        $referenceDate = $to;

        $this->dbg('step1', 'Eksekusi query transaksi', [
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'reference_date' => $referenceDate->toDateString(),
        ]);

        $rawData = DB::table('transactions as t')
            ->join('customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.status', 'completed')
            ->whereBetween('t.transaction_date', [$from, $to])
            ->whereNull('c.deleted_at')
            ->where('c.type', 'member')
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

        $this->dbg('step1', 'Query selesai', [
            'row_count' => $rawData->count(),
            'elapsed'   => $this->elapsed($t),
        ]);

        if ($rawData->isNotEmpty()) {
            $this->dbg('step1', 'Statistik distribusi raw data', [
                'recency_days'  => [
                    'min' => $rawData->min('recency_days'),
                    'max' => $rawData->max('recency_days'),
                    'avg' => round($rawData->avg('recency_days'), 1),
                ],
                'frequency' => [
                    'min' => $rawData->min('frequency'),
                    'max' => $rawData->max('frequency'),
                    'avg' => round($rawData->avg('frequency'), 1),
                ],
                'monetary' => [
                    'min' => $rawData->min('monetary'),
                    'max' => $rawData->max('monetary'),
                    'avg' => round($rawData->avg('monetary'), 2),
                ],
            ]);
        } else {
            $this->warn('step1', 'Query mengembalikan 0 baris — cek filter tanggal, status transaksi, &amp; customer_type=member');
        }

        $stats = [
            'count'            => $rawData->count(),
            'avg_recency_days' => round($rawData->avg('recency_days'), 1),
            'avg_frequency'    => round($rawData->avg('frequency'), 1),
            'avg_monetary'     => round($rawData->avg('monetary'), 2),
            'max_monetary'     => $rawData->max('monetary'),
            'min_recency_days' => $rawData->min('recency_days'),
        ];

        return [
            'step'        => 1,
            'title'       => 'Pengambilan data transaksi',
            'description' => "Mengambil data {$stats['count']} pelanggan bertipe member dengan transaksi completed dari {$from->format('d M Y')} hingga {$to->format('d M Y')}.",
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
        $t = $this->timer();

        $recencies   = $rawData->pluck('recency_days')->sort()->values();
        $frequencies = $rawData->pluck('frequency')->sort()->values();
        $monetaries  = $rawData->pluck('monetary')->sort()->values();

        $this->dbg('step2', 'Data diurutkan untuk quintile', [
            'recency_range'   => [$recencies->first(), $recencies->last()],
            'frequency_range' => [$frequencies->first(), $frequencies->last()],
            'monetary_range'  => [$monetaries->first(), $monetaries->last()],
        ]);

        $scored = $rawData->map(function ($row) use ($recencies, $frequencies, $monetaries) {
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

        $rBreaks = $this->quintileBreaks($recencies);
        $fBreaks = $this->quintileBreaks($frequencies);
        $mBreaks = $this->quintileBreaks($monetaries);

        $rDist = $this->scoreDistribution($scored, 'r_score');
        $fDist = $this->scoreDistribution($scored, 'f_score');
        $mDist = $this->scoreDistribution($scored, 'm_score');

        // Cek distribusi timpang: ada skor yang count-nya 0
        foreach (['r' => $rDist, 'f' => $fDist, 'm' => $mDist] as $metric => $dist) {
            $zeros = array_filter($dist, fn($v) => $v === 0);
            if (!empty($zeros)) {
                $this->warn('step2', "Distribusi quintile metric [{$metric}] memiliki skor kosong (count=0) — data mungkin terlalu sedikit atau duplikat banyak", [
                    'distribution' => $dist,
                ]);
            }
        }

        $this->dbg('step2', 'Quintile breakpoints', [
            'recency_breaks'   => $rBreaks,
            'frequency_breaks' => $fBreaks,
            'monetary_breaks'  => $mBreaks,
        ]);

        $this->dbg('step2', 'Score distribution', [
            'r' => $rDist,
            'f' => $fDist,
            'm' => $mDist,
            'elapsed' => $this->elapsed($t),
        ]);

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
                'r' => $rDist,
                'f' => $fDist,
                'm' => $mDist,
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

        if ($n === 0) {
            $this->warn('quintile', 'Sorted collection kosong, fallback ke skor 3', [
                'value'   => $value,
                'reverse' => $reverse,
            ]);
            return 3;
        }

        $rank       = $sorted->filter(fn($v) => $v <= $value)->count();
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
        $t = $this->timer();

        $rValues = $scored->pluck('recency_days');
        $fValues = $scored->pluck('frequency');
        $mValues = $scored->pluck('monetary');

        $normStats = [
            'recency'   => ['min' => $rValues->min(), 'max' => $rValues->max()],
            'frequency' => ['min' => $fValues->min(), 'max' => $fValues->max()],
            'monetary'  => ['min' => $mValues->min(), 'max' => $mValues->max()],
        ];

        $this->dbg('step3', 'Range tiap metrik sebelum normalisasi', $normStats);

        // Deteksi potensi masalah: min == max (semua nilai identik)
        foreach ($normStats as $metric => $range) {
            if ($range['min'] == $range['max']) {
                $this->warn('step3', "Metric [{$metric}] memiliki min == max — semua nilai akan dinormalisasi ke 0.5", [
                    'value' => $range['min'],
                ]);
            }
        }

        $normalized = $scored->map(function ($row) use ($normStats) {
            return (object) array_merge((array) $row, [
                'recency_norm'   => $this->minMax($row->recency_days, $normStats['recency']['min'],   $normStats['recency']['max'],   reverse: true),
                'frequency_norm' => $this->minMax($row->frequency,    $normStats['frequency']['min'], $normStats['frequency']['max']),
                'monetary_norm'  => $this->minMax($row->monetary,     $normStats['monetary']['min'],  $normStats['monetary']['max']),
            ]);
        });

        // Verifikasi range hasil normalisasi (harus 0–1)
        $rNorms = $normalized->pluck('recency_norm');
        $fNorms = $normalized->pluck('frequency_norm');
        $mNorms = $normalized->pluck('monetary_norm');

        $this->dbg('step3', 'Verifikasi range hasil normalisasi', [
            'recency_norm'   => ['min' => $rNorms->min(), 'max' => $rNorms->max()],
            'frequency_norm' => ['min' => $fNorms->min(), 'max' => $fNorms->max()],
            'monetary_norm'  => ['min' => $mNorms->min(), 'max' => $mNorms->max()],
            'elapsed'        => $this->elapsed($t),
        ]);

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
        if ($max == $min) {
            $this->warn('minMax', 'max == min, fallback ke 0.5', [
                'value' => $value, 'min' => $min, 'max' => $max, 'reverse' => $reverse,
            ]);
            return 0.5;
        }
        $norm = ($value - $min) / ($max - $min);
        return round($reverse ? (1 - $norm) : $norm, 6);
    }

    // =========================================================================
    // STEP 4 — K-Means clustering
    // =========================================================================
    private function stepKMeans($normalized, int $k, &$clustered, &$centroids, &$iterations, &$inertia): array
    {
        $t       = $this->timer();
        $maxIter = 100;
        // Konversi eksplisit ke array of array — ->toArray() pada Collection of stdClass
        // menghasilkan array of stdClass, bukan array of array (root cause error ini)
        $data = $normalized->values()->map(fn($row) => [
            'recency_norm'   => $row->recency_norm,
            'frequency_norm' => $row->frequency_norm,
            'monetary_norm'  => $row->monetary_norm,
        ])->toArray();
        $n       = count($data);

        $this->dbg('step4', "Memulai K-Means", [
            'n_points' => $n,
            'k'        => $k,
            'max_iter' => $maxIter,
        ]);

        if ($n < $k) {
            $this->err('step4', 'Jumlah data lebih kecil dari K — tidak bisa membuat cluster', [
                'n' => $n, 'k' => $k,
            ]);
            throw new \RuntimeException("Jumlah pelanggan ({$n}) lebih kecil dari jumlah cluster K={$k}.");
        }

        // Inisialisasi K-Means++
        $tInit = $this->timer();
        $centroids = $this->initCentroidsKMeansPlusPlus($data, $k);
        $this->dbg('step4', 'K-Means++ inisialisasi centroid awal', [
            'centroids' => array_map(fn($c) => array_map(fn($v) => round($v, 4), $c), $centroids),
            'elapsed'   => $this->elapsed($tInit),
        ]);

        $assignments = array_fill(0, $n, 0);
        $iterLog     = [];

        for ($iter = 0; $iter < $maxIter; $iter++) {
            // Assignment step
            $newAssignments = [];
            foreach ($data as $i => $point) {
                $newAssignments[$i] = $this->nearestCentroid($point, $centroids);
            }

            // SSE iterasi ini
            $sse = $this->calculateSSE($data, $newAssignments, $centroids);
            $iterLog[] = ['iteration' => $iter + 1, 'sse' => round($sse, 6)];

            // Log setiap 10 iterasi + iterasi pertama dan terakhir
            if ($iter === 0 || ($iter + 1) % 10 === 0) {
                $this->dbg('step4', "Iterasi #" . ($iter + 1), [
                    'sse'            => round($sse, 6),
                    'cluster_sizes'  => $this->clusterSizes($newAssignments, $k),
                ]);
            }

            // Cek konvergensi
            if ($newAssignments === $assignments && $iter > 0) {
                $assignments = $newAssignments;
                $iterations  = $iter + 1;
                $this->info('step4', "Konvergen pada iterasi #{$iterations}", [
                    'sse'     => round($sse, 6),
                    'elapsed' => $this->elapsed($t),
                ]);
                break;
            }
            $assignments = $newAssignments;

            // Update centroids
            $centroids = $this->updateCentroids($data, $assignments, $k);

            if ($iter === $maxIter - 1) {
                $iterations = $maxIter;
                $this->warn('step4', "Belum konvergen setelah {$maxIter} iterasi — batas maksimum tercapai", [
                    'final_sse' => round($sse, 6),
                ]);
            }
        }

        $inertia = $this->calculateSSE($data, $assignments, $centroids);

        // Deteksi cluster kosong
        $sizes = $this->clusterSizes($assignments, $k);
        foreach ($sizes as $clusterId => $count) {
            if ($count === 0) {
                $this->warn('step4', "Cluster #{$clusterId} kosong (0 anggota) setelah konvergensi", [
                    'all_sizes' => $sizes,
                ]);
            }
        }

        $this->info('step4', 'K-Means selesai', [
            'iterations'    => $iterations,
            'inertia'       => round($inertia, 6),
            'cluster_sizes' => $sizes,
            'elapsed'       => $this->elapsed($t),
        ]);

        // Gabungkan assignment ke normalized data
        $clustered = $normalized->map(function ($row, $i) use ($assignments, $centroids) {
            $clusterId = $assignments[$i];
            $centroid  = $centroids[$clusterId];
            $distance  = $this->euclidean(
                [$row->recency_norm, $row->frequency_norm, $row->monetary_norm],
                $centroid
            );

            return (object) array_merge((array) $row, [
                'cluster_id'           => $clusterId,
                'distance_to_centroid' => round($distance, 6),
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
            'cluster_sizes'   => $sizes,
        ];
    }

    private function initCentroidsKMeansPlusPlus(array $data, int $k): array
    {
        $this->dbg('kmeans++', "Inisialisasi {$k} centroid dengan K-Means++");

        $n = count($data);
        $centroids = [];

        // Centroid pertama: acak
        $firstIdx  = array_rand($data);
        $first     = $data[$firstIdx];
        $centroids = [[$first['recency_norm'], $first['frequency_norm'], $first['monetary_norm']]];

        $this->dbg('kmeans++', "Centroid pertama dipilih (idx={$firstIdx})", [
            'centroid' => $centroids[0],
        ]);

        for ($c = 1; $c < $k; $c++) {
            $distances = [];
            foreach ($data as $point) {
                $minDist = PHP_FLOAT_MAX;
                foreach ($centroids as $centroid) {
                    $d       = $this->euclidean([$point['recency_norm'], $point['frequency_norm'], $point['monetary_norm']], $centroid);
                    $minDist = min($minDist, $d * $d);
                }
                $distances[] = $minDist;
            }

            $total = array_sum($distances);

            if ($total <= 0) {
                $this->warn('kmeans++', "Total jarak kuadrat = 0 saat memilih centroid ke-{$c} — semua titik mungkin identik, fallback ke random");
                $chosen = array_rand($data);
            } else {
                $rand  = mt_rand() / mt_getrandmax() * $total;
                $cumul = 0;
                $chosen = count($data) - 1;
                foreach ($distances as $i => $d) {
                    $cumul += $d;
                    if ($cumul >= $rand) { $chosen = $i; break; }
                }
            }

            $p           = $data[$chosen];
            $centroids[] = [$p['recency_norm'], $p['frequency_norm'], $p['monetary_norm']];

            $this->dbg('kmeans++', "Centroid ke-{$c} dipilih (idx={$chosen})", [
                'centroid'        => end($centroids),
                'total_dist_sq'   => round($total, 6),
            ]);
        }

        return $centroids;
    }

    private function nearestCentroid(array $point, array $centroids): int
    {
        $minDist = PHP_FLOAT_MAX;
        $nearest = 0;
        $coords  = [$point['recency_norm'], $point['frequency_norm'], $point['monetary_norm']];

        foreach ($centroids as $i => $centroid) {
            $d = $this->euclidean($coords, $centroid);
            if ($d < $minDist) { $minDist = $d; $nearest = $i; }
        }
        return $nearest;
    }

    private function updateCentroids(array $data, array $assignments, int $k): array
    {
        $sums   = array_fill(0, $k, [0.0, 0.0, 0.0]);
        $counts = array_fill(0, $k, 0);

        foreach ($data as $i => $point) {
            $c = $assignments[$i];
            $sums[$c][0] += $point['recency_norm']   ?? 0;
            $sums[$c][1] += $point['frequency_norm'] ?? 0;
            $sums[$c][2] += $point['monetary_norm']  ?? 0;
            $counts[$c]++;
        }

        return array_map(function ($sum, $count) {
            if ($count === 0) {
                $this->warn('updateCentroids', 'Cluster kosong saat update centroid — fallback ke [0.5, 0.5, 0.5]');
                return [0.5, 0.5, 0.5];
            }
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
            $c    = $assignments[$i];
            $sse += ($point['recency_norm']   - $centroids[$c][0])**2
                  + ($point['frequency_norm'] - $centroids[$c][1])**2
                  + ($point['monetary_norm']  - $centroids[$c][2])**2;
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
    // STEP 5 — Auto-label segmen
    // =========================================================================
    private function stepAutoLabel(array $centroids, &$clusterLabels): array
    {
        $t = $this->timer();

        $centroidScores = array_map(fn($c) => ($c[0] + $c[1] + $c[2]) / 3, $centroids);

        $this->dbg('step5', 'Skor tiap centroid (R+F+M)/3', [
            'raw_scores' => array_map(fn($s) => round($s, 6), $centroidScores),
        ]);

        $labelPool = [
            'Champions', 'Loyal Customers', 'Potential Loyalists',
            'At Risk', 'Needs Attention', 'About to Sleep',
            'Lost Customers', 'New Customers', 'Hibernating', 'Promising',
        ];

        arsort($centroidScores);
        $clusterLabels = [];
        $labelIdx = 0;
        foreach ($centroidScores as $clusterId => $score) {
            $label = $labelPool[$labelIdx] ?? "Cluster {$clusterId}";
            $clusterLabels[(string) $clusterId] = $label;

            $this->dbg('step5', "Cluster #{$clusterId} → [{$label}]", [
                'centroid_score' => round($score, 6),
                'rank'           => $labelIdx + 1,
            ]);
            $labelIdx++;
        }
        ksort($clusterLabels);

        $this->info('step5', 'Label akhir per cluster', [
            'cluster_labels' => $clusterLabels,
            'elapsed'        => $this->elapsed($t),
        ]);

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
        $t             = $this->timer();
        $savedCount    = 0;
        $changedCount  = 0;
        $skippedCount  = 0;
        $clustered     = $clustered->keyBy('customer_id');

        $this->dbg('step6', 'Mulai DB transaction untuk persist RFM scores', [
            'batch_id'         => $batch->id,
            'raw_data_count'   => $rawData->count(),
            'clustered_count'  => $clustered->count(),
            'cluster_labels'   => $clusterLabels,
        ]);

        DB::transaction(function () use (
            $batch, $rawData, $scored, $normalized, $clustered,
            $clusterLabels, $centroids, $t, &$savedCount, &$changedCount, &$skippedCount
        ) {
            foreach ($rawData as $idx => $raw) {
                $cid = $raw->customer_id;
                $sc  = $scored->firstWhere('customer_id', $cid);
                $cl  = $clustered[$cid] ?? null;

                if (!$sc || !$cl) {
                    $skippedCount++;
                    $this->warn('step6', "Customer #{$cid} dilewati — data scored atau clustered tidak ditemukan", [
                        'has_scored'    => !is_null($sc),
                        'has_clustered' => !is_null($cl),
                    ]);
                    continue;
                }

                $segmentName = $clusterLabels[(string) ($cl->cluster_id ?? 0)] ?? 'Unknown';

                if ($segmentName === 'Unknown') {
                    $this->warn('step6', "Customer #{$cid} mendapat segmen 'Unknown' — cluster_id tidak ada di label map", [
                        'cluster_id'    => $cl->cluster_id,
                        'cluster_labels' => $clusterLabels,
                    ]);
                }

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

                $prevSegment = $lastHistory?->segment_to;
                $isChanged   = $prevSegment !== $segmentName;

                if ($isChanged && $prevSegment !== null) {
                    $changedCount++;
                    $this->dbg('step6', "Customer #{$cid} berpindah segmen", [
                        'from' => $prevSegment,
                        'to'   => $segmentName,
                    ]);
                }

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

                // Log progress setiap 500 record
                if ($savedCount % 500 === 0) {
                    $this->dbg('step6', "Progress persist: {$savedCount}/{$rawData->count()} disimpan", [
                        'elapsed' => $this->elapsed($t),
                    ]);
                }
            }
        });

        $this->info('step6', 'DB transaction selesai', [
            'saved'   => $savedCount,
            'changed' => $changedCount,
            'skipped' => $skippedCount,
            'elapsed' => $this->elapsed($t),
        ]);

        if ($skippedCount > 0) {
            $this->warn('step6', "{$skippedCount} pelanggan dilewati — periksa join antara rawData, scored, dan clustered");
        }

        return [
            'step'        => 6,
            'title'       => 'Menyimpan hasil ke database',
            'description' => "Berhasil menyimpan {$savedCount} skor RFM. {$changedCount} pelanggan berpindah segmen dibanding kalkulasi sebelumnya.",
            'stats'       => [
                'saved'   => $savedCount,
                'changed' => $changedCount,
                'skipped' => $skippedCount,
            ],
        ];
    }


    // =========================================================================
    // Elbow Method helper
    // =========================================================================

    /**
     * Menghitung SSE untuk nilai K tertentu tanpa melakukan full pipeline.
     * Digunakan untuk Elbow Method API.
     */
    public function calculateSseForK(array $data, int $k): float
    {
        $t = $this->timer();
        $this->dbg('elbow', "Hitung SSE untuk K={$k}", ['n_points' => count($data)]);

        $n = count($data);
        if ($n < $k) {
            $this->warn('elbow', "Data ({$n}) lebih kecil dari K={$k} — return SSE=0", ['n' => $n]);
            return 0.0;
        }


        $centroids   = $this->initCentroidsKMeansPlusPlus($data, $k);
        $assignments = [];
        $maxIter     = 20;

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $newAssignments = [];
            foreach ($data as $i => $point) {
                $newAssignments[$i] = $this->nearestCentroid($point, $centroids);
            }

            if ($newAssignments === $assignments) {
                $this->dbg('elbow', "K={$k} konvergen pada iterasi #" . ($iter + 1));
                break;
            }
            $assignments = $newAssignments;
            $centroids   = $this->updateCentroids($data, $assignments, $k);
        }

        $sse = $this->calculateSSE($data, $assignments, $centroids);

        $this->dbg('elbow', "SSE K={$k} selesai", [
            'sse'     => round($sse, 6),
            'elapsed' => $this->elapsed($t),
        ]);

        return $sse;
    }
}