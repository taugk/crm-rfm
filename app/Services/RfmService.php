<?php

namespace App\Services;

use App\Models\RfmCalculationBatch;
use App\Models\RfmScore;
use App\Models\RfmSegmentHistory;
use App\Models\RfmCustomerRaw;
use App\Models\RfmCustomerNormalized;
use App\Models\RfmKmeansIteration;
use App\Models\RfmCentroid;
use App\Models\RfmKmeansAssignment;
use App\Models\RfmDbiScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class RfmService
{
    // =========================================================================
    // Helper: unified logger ke channel 'rfm'
    // =========================================================================
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

    private function timer(): float
    {
        return microtime(true);
    }

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

        Log::channel('rfm')->info('[RFM][calculate] ===== KALKULASI DIMULAI =====', [
            'user_id'    => $userId,
            'k_clusters' => $kClusters,
            'from'       => $from->toDateString(),
            'to'         => $to->toDateString(),
        ]);

        $t = $this->timer();
        $batch = RfmCalculationBatch::create([
            'triggered_by'     => $userId,
            'k_clusters'       => $kClusters,
            'max_iterations'   => 10,
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
            $dbiScore   = null;
            $iterationDetails = [];

            // STEP 1: Raw data
            $t1 = $this->timer();
            $steps[] = $this->stepRawData($rawData, $from, $to);
            $this->info('step1', 'Raw data selesai', [
                'total_rows' => $rawData->count(),
                'elapsed'    => $this->elapsed($t1),
            ], $batch->id);
            if ($rawData->isEmpty()) {
                throw new \RuntimeException('Tidak ada data transaksi.');
            }
            $batch->update(['total_customers' => $rawData->count()]);

            // STEP 2: Quintile scoring
            $t2 = $this->timer();
            $steps[] = $this->stepQuintile($rawData, $scored);
            $this->info('step2', 'Quintile scoring selesai', [
                'total_scored' => $scored->count(),
                'elapsed'      => $this->elapsed($t2),
            ], $batch->id);

            // STEP 3: Normalisasi
            $t3 = $this->timer();
            $step3Result = $this->stepNormalize($scored, $normalized, $normStats);
            $steps[] = $step3Result;
            $this->info('step3', 'Normalisasi selesai', [
                'norm_stats' => $normStats,
                'elapsed'    => $this->elapsed($t3),
            ], $batch->id);

            // STEP 4: K-Means dengan detail iterasi
            $t4 = $this->timer();
            $kmeansResult = $this->kMeansColabStyle($normalized, $kClusters, 20, 8);
            $clustered        = $kmeansResult['clustered'];
            $centroids        = $kmeansResult['centroids'];
            $iterations       = $kmeansResult['iterations'];
            $inertia          = $kmeansResult['inertia'];
            $iterationDetails = $kmeansResult['iteration_details'];

            $steps[] = [
                'step'             => 4,
                'title'            => "K-Means Clustering (K={$kClusters})",
                'description'      => "Centroid awal = K data pertama, maksimal 8 iterasi. Konvergen setelah {$iterations} iterasi.",
                'iterations'       => $iterations,
                'inertia'          => round($inertia, 6),
                'iteration_details'=> $iterationDetails,
                'final_centroids'  => $centroids,
                'cluster_sizes'    => $this->clusterSizes($clustered->pluck('cluster_id')->toArray(), $kClusters),
            ];

            $this->info('step4', 'K-Means selesai (Colab style)', [
                'k'            => $kClusters,
                'iterations'   => $iterations,
                'inertia'      => round($inertia, 6),
                'cluster_sizes'=> $this->clusterSizes($clustered->pluck('cluster_id')->toArray(), $kClusters),
                'elapsed'      => $this->elapsed($t4),
            ], $batch->id);

            // STEP 5: Auto-label
            $t5 = $this->timer();
            $steps[] = $this->stepAutoLabel($centroids, $clusterLabels);
            $this->info('step5', 'Auto-label selesai', [
                'cluster_labels' => $clusterLabels,
                'elapsed'        => $this->elapsed($t5),
            ], $batch->id);

            // STEP 6: Persist
            $t6 = $this->timer();
            $steps[] = $this->stepPersist($batch, $rawData, $scored, $normalized, $clustered, $clusterLabels, $centroids, $savedCount, $iterationDetails);
            $this->info('step6', 'Persist selesai', [
                'saved_count' => $savedCount,
                'elapsed'     => $this->elapsed($t6),
            ], $batch->id);

            // STEP 7: Hitung DBI
            $t7 = $this->timer();
            $dbiScore = $this->computeDbi($normalized, $clustered, $centroids, $kClusters);
            $this->info('step7', 'DBI dihitung', [
                'dbi_score' => round($dbiScore, 6),
                'elapsed'   => $this->elapsed($t7),
            ], $batch->id);

            \App\Models\RfmDbiScore::create([
                'calculation_batch_id' => $batch->id,
                'k'                    => $kClusters,
                'dbi_score'            => round($dbiScore, 6),
                'cluster_details'      => null,
            ]);

            $steps[] = [
                'step'        => 7,
                'title'       => 'Perhitungan Davies-Bouldin Index (DBI)',
                'description' => 'Mengukur kualitas cluster. Semakin kecil DBI semakin baik.',
                'dbi_score'   => round($dbiScore, 6),
            ];

            // Finalisasi batch
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $batch->update([
                'status'           => 'completed',
                'actual_iterations'=> $iterations,
                'inertia'          => $inertia,
                'final_centroids'  => $centroids,
                'cluster_labels'   => $clusterLabels,
                'duration_ms'      => $durationMs,
                'dbi_score'        => round($dbiScore, 6),
            ]);

            Log::channel('rfm')->info('[RFM][calculate] ===== KALKULASI SELESAI =====', [
                'batch_id'    => $batch->id,
                'status'      => 'completed',
                'duration_ms' => $durationMs,
                'saved'       => $savedCount,
                'k'           => $kClusters,
                'iterations'  => $iterations,
                'inertia'     => round($inertia, 6),
                'dbi_score'   => round($dbiScore, 6),
            ]);

            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => true];

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            $this->err('calculate', 'Pipeline gagal', [
                'batch_id'    => $batch->id,
                'error'       => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $batch->id);
            return ['batch' => $batch->fresh(), 'steps' => $steps, 'success' => false, 'error' => $e->getMessage()];
        }
    }


    // =========================================================================
    // STEP 1: Raw data
    // =========================================================================
    private function stepRawData(&$rawData, Carbon $from, Carbon $to): array
    {
        $t = $this->timer();
        $referenceDate = $to;

        $rawData = DB::table('customers as c')
            ->leftJoin('transactions as t', function($join) use ($from, $to) {
                $join->on('c.id', '=', 't.customer_id')
                     ->where('t.status', 'completed')
                     ->whereBetween('t.transaction_date', [$from, $to]);
            })
            ->where('c.type', 'member')
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.name', 'c.email')
            ->selectRaw("
                c.id AS customer_id,
                c.name,
                c.email,
                COALESCE(DATEDIFF(?, MAX(t.transaction_date)), 999999) AS recency_days,
                COALESCE(COUNT(t.id), 0) AS frequency,
                COALESCE(SUM(t.total_price), 0) AS monetary,
                MAX(t.transaction_date) AS last_transaction_date,
                MIN(t.transaction_date) AS first_transaction_date
            ", [$referenceDate])
            ->orderBy('c.id')
            ->get();

        $maxRecency = $rawData->max('recency_days');
        if ($maxRecency == 999999) {
            $earliestPossible = $from->copy()->subYears(10);
            $defaultRecency = $referenceDate->diffInDays($earliestPossible);
            $rawData = $rawData->map(function($row) use ($defaultRecency) {
                if ($row->recency_days == 999999) {
                    $row->recency_days = $defaultRecency;
                }
                return $row;
            });
        }

        $stats = [
            'count'            => $rawData->count(),
            'avg_recency_days' => round($rawData->avg('recency_days'), 1),
            'avg_frequency'    => round($rawData->avg('frequency'), 1),
            'avg_monetary'     => round($rawData->avg('monetary'), 2),
        ];

        return [
            'step'        => 1,
            'title'       => 'Pengambilan data transaksi',
            'description' => "Mengambil data {$stats['count']} pelanggan (semua member).",
            'stats'       => $stats,
        ];
    }

    // =========================================================================
    // STEP 2: Quintile scoring
    // =========================================================================
    private function stepQuintile($rawData, &$scored): array
    {
        $recencies   = $rawData->pluck('recency_days')->sort()->values();
        $frequencies = $rawData->pluck('frequency')->sort()->values();
        $monetaries  = $rawData->pluck('monetary')->sort()->values();

        $scored = $rawData->map(function ($row) use ($recencies, $frequencies, $monetaries) {
            $rScore = $this->quintile($row->recency_days, $recencies, true);
            $fScore = $this->quintile($row->frequency, $frequencies, false);
            $mScore = $this->quintile($row->monetary, $monetaries, false);
            return (object) array_merge((array) $row, [
                'r_score'   => $rScore,
                'f_score'   => $fScore,
                'm_score'   => $mScore,
                'rfm_score' => round(($rScore + $fScore + $mScore) / 3, 2),
                'rfm_label' => "{$rScore}{$fScore}{$mScore}",
            ]);
        });

        return [
            'step'        => 2,
            'title'       => 'Scoring quintile R, F, M',
            'description' => 'Skor 1–5 berdasarkan kuintil. Recency dibalik.',
        ];
    }

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
            default => 5,
        };
        return $reverse ? (6 - $score) : $score;
    }

    // =========================================================================
    // STEP 3: Normalisasi Min-Max
    // =========================================================================
    private function stepNormalize($scored, &$normalized, &$normStats): array
    {
        $rVals = $scored->pluck('recency_days');
        $fVals = $scored->pluck('frequency');
        $mVals = $scored->pluck('monetary');

        $normStats = [
            'recency'   => ['min' => $rVals->min(), 'max' => $rVals->max()],
            'frequency' => ['min' => $fVals->min(), 'max' => $fVals->max()],
            'monetary'  => ['min' => $mVals->min(), 'max' => $mVals->max()],
        ];

        $normalized = $scored->map(function ($row) use ($normStats) {
            return (object) array_merge((array) $row, [
                'recency_norm'   => $this->minMax($row->recency_days, $normStats['recency']['min'], $normStats['recency']['max'], true),
                'frequency_norm' => $this->minMax($row->frequency, $normStats['frequency']['min'], $normStats['frequency']['max']),
                'monetary_norm'  => $this->minMax($row->monetary, $normStats['monetary']['min'], $normStats['monetary']['max']),
            ]);
        });

        $sample = $normalized->take(10)->map(fn($r) => [
            'customer_id'    => $r->customer_id,
            'recency_days'   => $r->recency_days,
            'frequency'      => $r->frequency,
            'monetary'       => $r->monetary,
            'recency_norm'   => $r->recency_norm,
            'frequency_norm' => $r->frequency_norm,
            'monetary_norm'  => $r->monetary_norm,
        ])->toArray();

        return [
            'step'        => 3,
            'title'       => 'Normalisasi Min-Max (0–1)',
            'description' => 'Nilai R, F, M dinormalisasi ke 0–1. Recency dibalik: makin kecil recency (baru) makin besar nilai norm.',
            'stats'       => $normStats,
            'sample'      => $sample,
        ];
    }

    private function minMax($value, $min, $max, bool $reverse = false): float
    {
        if ($max == $min) return 0.5;
        $norm = ($value - $min) / ($max - $min);
        return round($reverse ? (1 - $norm) : $norm, 6);
    }

    // =========================================================================
    // STEP 4: K-Means ala Colab
    // FIX: iterasi dicatat SEBELUM cek konvergensi, sehingga iterasi konvergen
    //      tetap masuk ke $iterationDetails dan count() akurat.
    // =========================================================================
    private function kMeansColabStyle($normalized, int $k, int $maxIter = 8, int $minIter = 8): array
    {
        $points = $normalized->map(fn($row) => [
            $row->recency_norm,
            $row->frequency_norm,
            $row->monetary_norm,
        ])->toArray();

        $n = count($points);
        if ($n < $k) {
            throw new \RuntimeException("Jumlah data ({$n}) < K ({$k})");
        }

        // Centroid awal = K data pertama (urutan asli dari database)
        $centroids        = array_slice($points, 0, $k);
        $iterationDetails = [];
        $prevAssignments  = [];

        for ($iter = 0; $iter < $maxIter; $iter++) {

            // --- Assignment ---
            $assignments = [];
            foreach ($points as $idx => $point) {
                $minDist = INF;
                $best    = 0;
                foreach ($centroids as $cId => $cent) {
                    $dist = $this->euclidean($point, $cent);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $best    = $cId;
                    }
                }
                $assignments[$idx] = $best;
            }

            // --- Catat iterasi ini SEBELUM cek konvergensi ---
            $clusterSizes = array_count_values($assignments);
            $iterationDetails[] = [
                'iteration'     => $iter + 1,
                'cluster_sizes' => $clusterSizes,
                'centroids'     => $centroids,
            ];

            // --- Cek konvergensi: hanya berlaku setelah minimal $minIter iterasi ---
            // $iter berbasis 0, jadi iterasi ke-$minIter = $iter >= ($minIter - 1)
            $reachedMinIter = ($iter >= ($minIter - 1));
            $converged      = $reachedMinIter && ($prevAssignments !== []) && ($assignments === $prevAssignments);
            $prevAssignments = $assignments;

            if ($converged) {
                // Berhenti; iterasi konvergen sudah tercatat di atas
                break;
            }

            // --- Update centroids ---
            $newCentroids = array_fill(0, $k, [0.0, 0.0, 0.0]);
            $counts       = array_fill(0, $k, 0);
            foreach ($points as $idx => $point) {
                $c = $assignments[$idx];
                $newCentroids[$c][0] += $point[0];
                $newCentroids[$c][1] += $point[1];
                $newCentroids[$c][2] += $point[2];
                $counts[$c]++;
            }
            foreach ($newCentroids as $cId => &$sum) {
                if ($counts[$cId] > 0) {
                    $sum = [
                        $sum[0] / $counts[$cId],
                        $sum[1] / $counts[$cId],
                        $sum[2] / $counts[$cId],
                    ];
                } else {
                    // Cluster kosong: tetap pakai centroid lama
                    $sum = $centroids[$cId];
                }
            }
            unset($sum);
            $centroids = $newCentroids;
        }

        // Hitung inertia (SSE) menggunakan assignment & centroid terakhir
        $inertia = 0.0;
        foreach ($points as $idx => $point) {
            $c       = $prevAssignments[$idx];
            $inertia += $this->euclidean($point, $centroids[$c]) ** 2;
        }

        // Gabungkan hasil ke collection $normalized
        $clustered = $normalized->map(function ($row, $i) use ($prevAssignments, $centroids) {
            $clusterId = $prevAssignments[$i];
            $centroid  = $centroids[$clusterId];
            $distance  = $this->euclidean(
                [$row->recency_norm, $row->frequency_norm, $row->monetary_norm],
                $centroid
            );
            $row->cluster_id           = $clusterId;
            $row->distance_to_centroid = round($distance, 6);
            return $row;
        });

        return [
            'clustered'         => $clustered,
            'centroids'         => $centroids,
            'iterations'        => count($iterationDetails), // akurat: termasuk iterasi konvergen
            'inertia'           => $inertia,
            'iteration_details' => $iterationDetails,
        ];
    }

    private function euclidean(array $a, array $b): float
    {
        return sqrt(($a[0]-$b[0])**2 + ($a[1]-$b[1])**2 + ($a[2]-$b[2])**2);
    }

    private function clusterSizes(array $assignments, int $k): array
    {
        $sizes = array_fill(0, $k, 0);
        foreach ($assignments as $c) $sizes[$c]++;
        return $sizes;
    }

    // =========================================================================
    // STEP 5: Auto-label segmen
    // =========================================================================
    private function stepAutoLabel(array $centroids, &$clusterLabels): array
    {
        $scores = array_map(fn($c) => ($c[0] + $c[1] + $c[2]) / 3, $centroids);
        arsort($scores);
        $labelPool = [
            'Champions', 'Loyal Customers', 'Potential Loyalists',
            'At Risk', 'Needs Attention', 'About to Sleep',
            'Lost Customers', 'New Customers', 'Hibernating', 'Promising',
        ];
        $clusterLabels = [];
        $idx = 0;
        foreach ($scores as $clusterId => $score) {
            $clusterLabels[(string) $clusterId] = $labelPool[$idx] ?? "Cluster {$clusterId}";
            $idx++;
        }
        ksort($clusterLabels);
        return [
            'step'        => 5,
            'title'       => 'Auto-labeling segmen',
            'description' => 'Label berdasarkan rata-rata centroid.',
            'labels'      => $clusterLabels,
        ];
    }

    // =========================================================================
    // STEP 6: Persist semua data ke database
    // =========================================================================
    private function stepPersist($batch, $rawData, $scored, $normalized, $clustered, array $clusterLabels, array $centroids, &$savedCount, array $iterationDetails = []): array
    {
        $savedCount   = 0;
        $changedCount = 0;
        $skippedCount = 0;
        $clustered    = $clustered->keyBy('customer_id');

        // -----------------------------------------------------------------
        // 1. Simpan RfmScore & RfmSegmentHistory
        // -----------------------------------------------------------------
        DB::transaction(function () use ($batch, $rawData, $scored, $clustered, $clusterLabels, &$savedCount, &$changedCount, &$skippedCount) {
            foreach ($rawData as $raw) {
                $cid = $raw->customer_id;
                $sc  = $scored->firstWhere('customer_id', $cid);
                $cl  = $clustered[$cid] ?? null;
                if (!$sc || !$cl) {
                    $skippedCount++;
                    continue;
                }
                $segmentName = $clusterLabels[(string) ($cl->cluster_id ?? 0)] ?? 'Unknown';

                $rfmScore = RfmScore::create([
                    'customer_id'          => $cid,
                    'recency_days'         => $raw->recency_days,
                    'frequency'            => $raw->frequency,
                    'monetary'             => $raw->monetary,
                    'recency_norm'         => $cl->recency_norm,
                    'frequency_norm'       => $cl->frequency_norm,
                    'monetary_norm'        => $cl->monetary_norm,
                    'r_score'              => $sc->r_score,
                    'f_score'              => $sc->f_score,
                    'm_score'              => $sc->m_score,
                    'rfm_score'            => $sc->rfm_score,
                    'rfm_label'            => $sc->rfm_label,
                    'cluster_id'           => $cl->cluster_id,
                    'segment_name'         => $segmentName,
                    'distance_to_centroid' => $cl->distance_to_centroid,
                    'calculation_batch_id' => $batch->id,
                ]);

                $lastHistory = RfmSegmentHistory::where('customer_id', $cid)->latest()->first();
                $prevSegment = $lastHistory?->segment_to;
                $isChanged   = $prevSegment !== $segmentName;
                if ($isChanged && $prevSegment !== null) $changedCount++;

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

        // -----------------------------------------------------------------
        // 2. Simpan raw data ke rfm_customer_raw
        // -----------------------------------------------------------------
        foreach ($rawData as $raw) {
            RfmCustomerRaw::updateOrCreate(
                ['calculation_batch_id' => $batch->id, 'customer_id' => $raw->customer_id],
                [
                    'recency_days' => $raw->recency_days,
                    'frequency'    => $raw->frequency,
                    'monetary'     => $raw->monetary,
                ]
            );
        }

        // -----------------------------------------------------------------
        // 3. Simpan normalized data ke rfm_customer_normalized
        // -----------------------------------------------------------------
        $normalizedKeyed = $normalized->keyBy('customer_id');
        foreach ($rawData as $raw) {
            $norm = $normalizedKeyed[$raw->customer_id] ?? null;
            if (!$norm) continue;
            RfmCustomerNormalized::updateOrCreate(
                ['calculation_batch_id' => $batch->id, 'customer_id' => $raw->customer_id],
                [
                    'recency_norm'   => $norm->recency_norm,
                    'frequency_norm' => $norm->frequency_norm,
                    'monetary_norm'  => $norm->monetary_norm,
                ]
            );
        }

        // -----------------------------------------------------------------
        // 4. Simpan iterasi, centroid, dan assignments
        // -----------------------------------------------------------------
        if (!empty($iterationDetails)) {
            foreach ($iterationDetails as $iterData) {
                $iterNum       = $iterData['iteration'];
                $clusterSizes  = $iterData['cluster_sizes'] ?? [];
                $centroidsIter = $iterData['centroids'] ?? [];

                $allNorm = RfmCustomerNormalized::where('calculation_batch_id', $batch->id)->get();

                $totalWcss        = 0;
                $assignmentsBuffer = [];

                foreach ($allNorm as $norm) {
                    $distances = [];
                    foreach ($centroidsIter as $cId => $cent) {
                        $dist        = $this->euclidean(
                            [$norm->recency_norm, $norm->frequency_norm, $norm->monetary_norm],
                            $cent
                        );
                        $distances[] = round($dist, 6);
                    }
                    $minDist        = min($distances);
                    $assignedCluster = array_search($minDist, $distances);
                    $totalWcss      += pow($minDist, 2);

                    $assignmentsBuffer[] = [
                        'customer_id' => $norm->customer_id,
                        'cluster_id'  => $assignedCluster,
                        'min_dist'    => $minDist,
                        'distances'   => $distances,
                    ];
                }

                RfmKmeansIteration::updateOrCreate(
                    ['calculation_batch_id' => $batch->id, 'iteration_number' => $iterNum],
                    [
                        'wcss'                => $totalWcss,
                        'assignments_changed' => ($iterNum > 1),
                        'cluster_sizes'       => $clusterSizes,
                        'is_converged'        => ($iterNum == count($iterationDetails)),
                    ]
                );

                foreach ($centroidsIter as $cId => $cent) {
                    RfmCentroid::updateOrCreate(
                        [
                            'calculation_batch_id' => $batch->id,
                            'iteration_number'     => $iterNum,
                            'cluster_id'           => $cId,
                        ],
                        [
                            'recency_pos'   => $cent[0],
                            'frequency_pos' => $cent[1],
                            'monetary_pos'  => $cent[2],
                        ]
                    );
                }

                foreach ($assignmentsBuffer as $assign) {
                    RfmKmeansAssignment::updateOrCreate(
                        [
                            'calculation_batch_id' => $batch->id,
                            'iteration_number'     => $iterNum,
                            'customer_id'          => $assign['customer_id'],
                        ],
                        [
                            'cluster_id'                => $assign['cluster_id'],
                            'distance_to_centroid'      => $assign['min_dist'],
                            'distances_to_all_centroids'=> $assign['distances'],
                        ]
                    );
                }
            }
        }

        return [
            'step'        => 6,
            'title'       => 'Menyimpan hasil ke database',
            'description' => "Tersimpan {$savedCount} skor RFM. Perubahan segmen: {$changedCount}. Data raw, normalized, iterasi, centroid, assignment juga disimpan.",
            'stats'       => ['saved' => $savedCount, 'changed' => $changedCount, 'skipped' => $skippedCount],
        ];
    }


    // =========================================================================
    // METHOD TAMBAHAN: Elbow & DBI (untuk keperluan API)
    // =========================================================================

    /**
     * Hitung SSE (inertia) untuk berbagai nilai K (elbow method)
     */
    public function calculateElbow(array $data, int $maxK = 10): array
    {
        $result = [];
        for ($k = 1; $k <= $maxK; $k++) {
            if ($k > count($data)) {
                $result[$k] = 0;
                continue;
            }
            $kmeans    = $this->kMeansColabStyle($this->arrayToCollection($data), $k, 20, 8);
            $result[$k] = round($kmeans['inertia'], 6);
        }
        return $result;
    }

    /**
     * Hitung DBI untuk k=2 sampai maxK
     */
    public function calculateDbiComparison(int $userId, int $maxK = 10, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $startTime = microtime(true);
        $to        = $to   ?? Carbon::now();
        $from      = $from ?? Carbon::now()->subYears(2);

        $rawData = $this->getRawData($from, $to);
        if ($rawData->isEmpty()) {
            throw new \RuntimeException('Tidak ada data transaksi dalam rentang tanggal yang dipilih.');
        }

        [$normalized] = $this->normalizeData($rawData);

        $dbiResults = [];
        $bestK      = 2;
        $bestDbi    = PHP_FLOAT_MAX;

        for ($k = 2; $k <= $maxK; $k++) {
            if ($k > $normalized->count()) break;
            $kmeans         = $this->kMeansColabStyle($normalized, $k, 20, 8);
            $clustered      = $kmeans['clustered'];
            $finalCentroids = $kmeans['centroids'];
            $dbi            = $this->computeDbi($normalized, $clustered, $finalCentroids, $k);
            $dbiResults[]   = ['k' => $k, 'dbi' => round($dbi, 6), 'is_best' => false];

            if ($dbi < $bestDbi) {
                $bestDbi = $dbi;
                $bestK   = $k;
            }
        }

        foreach ($dbiResults as &$r) {
            $r['is_best'] = ($r['k'] === $bestK);
        }

        return [
            'dbi_results'     => $dbiResults,
            'best_k'          => $bestK,
            'best_dbi'        => round($bestDbi, 6),
            'total_customers' => $rawData->count(),
            'from'            => $from->toDateString(),
            'to'              => $to->toDateString(),
            'duration_ms'     => (int) ((microtime(true) - $startTime) * 1000),
        ];
    }

    /**
     * Hitung Davies-Bouldin Index untuk hasil clustering (public, array-based)
     */
    public function calculateDaviesBouldin(array $data, array $labels): float
    {
        $points       = $data;
        $uniqueLabels = array_unique($labels);
        $k            = count($uniqueLabels);
        if ($k < 2) return 0.0;

        $centroids    = [];
        $clusterSizes = array_fill(0, $k, 0);
        $clusterSums  = array_fill(0, $k, [0.0, 0.0, 0.0]);
        foreach ($points as $idx => $p) {
            $c = $labels[$idx];
            $clusterSums[$c][0]  += $p[0];
            $clusterSums[$c][1]  += $p[1];
            $clusterSums[$c][2]  += $p[2];
            $clusterSizes[$c]++;
        }
        foreach ($uniqueLabels as $c) {
            $centroids[$c] = [
                $clusterSums[$c][0] / $clusterSizes[$c],
                $clusterSums[$c][1] / $clusterSizes[$c],
                $clusterSums[$c][2] / $clusterSizes[$c],
            ];
        }

        $scatter = [];
        foreach ($uniqueLabels as $c) {
            $sumDist = 0;
            foreach ($points as $idx => $p) {
                if ($labels[$idx] == $c) {
                    $sumDist += $this->euclidean($p, $centroids[$c]);
                }
            }
            $scatter[$c] = $sumDist / $clusterSizes[$c];
        }

        $dbi = 0;
        foreach ($uniqueLabels as $i) {
            $maxRatio = 0;
            foreach ($uniqueLabels as $j) {
                if ($i == $j) continue;
                $distCentroids = $this->euclidean($centroids[$i], $centroids[$j]);
                if ($distCentroids == 0) continue;
                $ratio = ($scatter[$i] + $scatter[$j]) / $distCentroids;
                if ($ratio > $maxRatio) $maxRatio = $ratio;
            }
            $dbi += $maxRatio;
        }
        return round($dbi / $k, 6);
    }

    private function arrayToCollection(array $data): \Illuminate\Support\Collection
    {
        return collect($data)->map(fn($point) => (object) [
            'recency_norm'   => $point[0],
            'frequency_norm' => $point[1],
            'monetary_norm'  => $point[2],
        ]);
    }

    /**
     * Hitung SSE untuk satu nilai K tertentu (Elbow method di controller)
     */
    public function calculateSseForK(array $data, int $k): float
    {
        $n = count($data);
        if ($n < $k) return 0.0;

        $centroids = array_slice($data, 0, $k);
        $maxIter   = 20;
        $prevAssign = [];

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $assign = [];
            foreach ($data as $idx => $point) {
                $minDist = INF;
                $best    = 0;
                foreach ($centroids as $cId => $cent) {
                    $dist = $this->euclidean($point, $cent);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $best    = $cId;
                    }
                }
                $assign[$idx] = $best;
            }
            if ($prevAssign === $assign) break;
            $prevAssign = $assign;

            $sums   = array_fill(0, $k, [0.0, 0.0, 0.0]);
            $counts = array_fill(0, $k, 0);
            foreach ($data as $idx => $point) {
                $c = $assign[$idx];
                $sums[$c][0] += $point[0];
                $sums[$c][1] += $point[1];
                $sums[$c][2] += $point[2];
                $counts[$c]++;
            }
            foreach ($sums as $cId => &$sum) {
                if ($counts[$cId] > 0) {
                    $sum = [
                        $sum[0] / $counts[$cId],
                        $sum[1] / $counts[$cId],
                        $sum[2] / $counts[$cId],
                    ];
                } else {
                    $sum = $centroids[$cId];
                }
            }
            unset($sum);
            $centroids = $sums;
        }

        $sse = 0.0;
        foreach ($data as $idx => $point) {
            $c    = $prevAssign[$idx];
            $sse += $this->euclidean($point, $centroids[$c]) ** 2;
        }
        return $sse;
    }

    /**
     * Get raw data from transactions
     */
    private function getRawData(Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        $referenceDate = $to;
        $rawData = DB::table('customers as c')
            ->leftJoin('transactions as t', function($join) use ($from, $to) {
                $join->on('c.id', '=', 't.customer_id')
                     ->where('t.status', 'completed')
                     ->whereBetween('t.transaction_date', [$from, $to]);
            })
            ->where('c.type', 'member')
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.name', 'c.email')
            ->selectRaw("
                c.id AS customer_id,
                c.name,
                c.email,
                COALESCE(DATEDIFF(?, MAX(t.transaction_date)), 999999) AS recency_days,
                COALESCE(COUNT(t.id), 0) AS frequency,
                COALESCE(SUM(t.total_price), 0) AS monetary,
                MAX(t.transaction_date) AS last_transaction_date,
                MIN(t.transaction_date) AS first_transaction_date
            ", [$referenceDate])
            ->orderBy('c.id')
            ->get();

        $maxRecency = $rawData->max('recency_days');
        if ($maxRecency == 999999) {
            $earliestPossible = $from->copy()->subYears(10);
            $defaultRecency   = $referenceDate->diffInDays($earliestPossible);
            $rawData = $rawData->map(function($row) use ($defaultRecency) {
                if ($row->recency_days == 999999) {
                    $row->recency_days = $defaultRecency;
                }
                return $row;
            });
        }
        return $rawData;
    }

    /**
     * Normalisasi Min-Max — kembalikan [collection, stats]
     */
    private function normalizeData(\Illuminate\Support\Collection $rawData): array
    {
        $rVals = $rawData->pluck('recency_days');
        $fVals = $rawData->pluck('frequency');
        $mVals = $rawData->pluck('monetary');

        $normStats = [
            'recency'   => ['min' => $rVals->min(), 'max' => $rVals->max()],
            'frequency' => ['min' => $fVals->min(), 'max' => $fVals->max()],
            'monetary'  => ['min' => $mVals->min(), 'max' => $mVals->max()],
        ];

        $normalized = $rawData->map(function ($row) use ($normStats) {
            return (object) [
                'customer_id'    => $row->customer_id,
                'recency_norm'   => $this->minMax($row->recency_days, $normStats['recency']['min'],   $normStats['recency']['max'],   true),
                'frequency_norm' => $this->minMax($row->frequency,    $normStats['frequency']['min'], $normStats['frequency']['max']),
                'monetary_norm'  => $this->minMax($row->monetary,     $normStats['monetary']['min'],  $normStats['monetary']['max']),
            ];
        });

        return [$normalized, $normStats];
    }

    /**
     * Hitung Davies-Bouldin Index (private, collection-based)
     */
    private function computeDbi(\Illuminate\Support\Collection $normalized, \Illuminate\Support\Collection $clustered, array $finalCentroids, int $k): float
    {
        $points      = $normalized->map(fn($r) => [
            $r->recency_norm,
            $r->frequency_norm,
            $r->monetary_norm,
        ])->toArray();

        $assignments = $clustered->pluck('cluster_id')->toArray();

        $si     = array_fill(0, $k, 0.0);
        $counts = array_fill(0, $k, 0);

        foreach ($points as $i => $point) {
            $ci       = $assignments[$i];
            $si[$ci] += $this->euclidean($point, $finalCentroids[$ci]);
            $counts[$ci]++;
        }

        for ($ci = 0; $ci < $k; $ci++) {
            $si[$ci] = $counts[$ci] > 0 ? $si[$ci] / $counts[$ci] : 0.0;
        }

        $di = array_fill(0, $k, 0.0);
        for ($i = 0; $i < $k; $i++) {
            for ($j = 0; $j < $k; $j++) {
                if ($i === $j) continue;
                $dij = $this->euclidean($finalCentroids[$i], $finalCentroids[$j]);
                $rij = $dij > 0 ? ($si[$i] + $si[$j]) / $dij : 0.0;
                if ($rij > $di[$i]) $di[$i] = $rij;
            }
        }

        return array_sum($di) / $k;
    }
}