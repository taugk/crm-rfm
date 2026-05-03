<?php

namespace App\Http\Controllers\RFM;

use App\Http\Controllers\Controller;
use App\Models\RfmCalculationBatch;
use App\Models\RfmCentroid;
use App\Models\RfmCustomerNormalized;
use App\Models\RfmCustomerRaw;
use App\Models\RfmDbiScore;
use App\Models\RfmKmeansAssignment;
use App\Models\RfmKmeansIteration;
use App\Models\RfmScore;
use App\Models\RfmSegmentHistory;
use App\Services\RfmService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
    public function __construct(private readonly RfmService $rfmService) {}

    public function index(Request $request): JsonResponse
    {
        Log::info('RFM Index: Start', ['user_id' => $request->user()?->id]);

        try {
            $latestBatch = RfmCalculationBatch::with('dbiScore')
                ->where('status', 'completed')
                ->latest()
                ->first();

            $totalBatches     = RfmCalculationBatch::count();
            $completedBatches = RfmCalculationBatch::where('status', 'completed')->count();
            $failedBatches    = RfmCalculationBatch::where('status', 'failed')->count();
            $runningBatches   = RfmCalculationBatch::where('status', 'running')->count();

            $segmentSummary = [];
            $dbi            = null;

            if ($latestBatch) {
                $segmentSummary = RfmScore::where('calculation_batch_id', $latestBatch->id)
                    ->selectRaw('
                        cluster_id,
                        segment_name,
                        COUNT(*) as total,
                        ROUND(AVG(rfm_score), 4) as avg_rfm_score,
                        ROUND(AVG(recency_days), 1) as avg_recency,
                        ROUND(AVG(frequency), 1) as avg_frequency,
                        ROUND(AVG(monetary), 2) as avg_monetary
                    ')
                    ->groupBy('cluster_id', 'segment_name')
                    ->orderBy('cluster_id')
                    ->get()
                    ->map(fn($r) => [
                        'cluster_id'    => $r->cluster_id,
                        'cluster_label' => 'C' . ($r->cluster_id + 1),
                        'segment_name'  => $r->segment_name,
                        'total'         => $r->total,
                        'avg_rfm_score' => $r->avg_rfm_score,
                        'avg_recency'   => $r->avg_recency,
                        'avg_frequency' => $r->avg_frequency,
                        'avg_monetary'  => $r->avg_monetary,
                    ]);

                $dbi = $latestBatch->dbiScore;
            }

            $recentBatches = RfmCalculationBatch::with(['triggeredBy:id,name', 'dbiScore'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($b) => [
                    'id'                => $b->id,
                    'triggered_by'      => $b->triggeredBy?->name ?? '-',
                    'k_clusters'        => $b->k_clusters,
                    'total_customers'   => $b->total_customers,
                    'actual_iterations' => $b->actual_iterations,
                    'inertia'           => $b->inertia,
                    'dbi_score'         => $b->dbiScore?->dbi_score,
                    'status'            => $b->status,
                    'duration_ms'       => $b->duration_ms,
                    'data_from'         => $b->data_from,
                    'data_to'           => $b->data_to,
                    'created_at'        => $b->created_at,
                ]);

            $response = [
                'latest_batch'    => $latestBatch ? [
                    'id'                => $latestBatch->id,
                    'k_clusters'        => $latestBatch->k_clusters,
                    'actual_iterations' => $latestBatch->actual_iterations,
                    'total_customers'   => $latestBatch->total_customers,
                    'inertia'           => $latestBatch->inertia,
                    'dbi_score'         => $latestBatch->dbiScore?->dbi_score,
                    'cluster_labels'    => $latestBatch->cluster_labels,
                    'data_from'         => $latestBatch->data_from,
                    'data_to'           => $latestBatch->data_to,
                    'duration_ms'       => $latestBatch->duration_ms,
                    'created_at'        => $latestBatch->created_at,
                ] : null,
                'dbi'             => $dbi,
                'segment_summary' => $segmentSummary,
                'batch_stats'     => [
                    'total'     => $totalBatches,
                    'completed' => $completedBatches,
                    'failed'    => $failedBatches,
                    'running'   => $runningBatches,
                ],
                'total_customers' => $latestBatch?->total_customers ?? 0,
                'recent_batches'  => $recentBatches,
            ];

            Log::info('RFM Index: Success', [
                'user_id'          => $request->user()?->id,
                'latest_batch_id'  => $latestBatch?->id,
                'total_customers'  => $latestBatch?->total_customers ?? 0,
                'batch_stats'      => $response['batch_stats'],
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('RFM Index: Exception', [
                'user_id'    => $request->user()?->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Calculate: Start', ['user_id' => $userId, 'input' => $request->only('k_clusters', 'from', 'to')]);

        $validator = validator($request->all(), [
            'k_clusters' => ['required', 'integer', 'min:2', 'max:10'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM Calculate: Validation failed', [
                'user_id' => $userId,
                'errors'  => $validator->errors()->toArray(),
            ]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->rfmService->calculate(
                userId:    $userId,
                kClusters: (int) $request->k_clusters,
                from:      $request->from ? Carbon::parse($request->from) : null,
                to:        $request->to   ? Carbon::parse($request->to)   : null,
            );

            if (! $result['success']) {
                Log::error('RFM Calculate: Service failed', [
                    'user_id'  => $userId,
                    'error'    => $result['error'],
                    'batch_id' => $result['batch']->id ?? null,
                ]);
                return response()->json([
                    'message'  => 'Kalkulasi RFM gagal.',
                    'error'    => $result['error'],
                    'batch_id' => $result['batch']->id,
                ], 422);
            }

            Log::info('RFM Calculate: Success', [
                'user_id'  => $userId,
                'batch_id' => $result['batch']->id,
                'k'        => $result['batch']->k_clusters,
                'customers'=> $result['batch']->total_customers,
            ]);

            return response()->json([
                'message'  => 'Kalkulasi RFM berhasil.',
                'batch_id' => $result['batch']->id,
                'batch'    => $result['batch'],
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Calculate: Exception', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan server', 'error' => $e->getMessage()], 500);
        }
    }

    public function elbow(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Elbow: Start', ['user_id' => $userId, 'input' => $request->only('from', 'to', 'max_k')]);

        $validator = validator($request->all(), [
            'from'  => ['nullable', 'date'],
            'to'    => ['nullable', 'date', 'after_or_equal:from'],
            'max_k' => ['nullable', 'integer', 'min:2', 'max:15'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM Elbow: Validation failed', ['user_id' => $userId, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $from = $request->from ? Carbon::parse($request->from) : Carbon::now()->subYears(2);
            $to   = $request->to   ? Carbon::parse($request->to)   : Carbon::now();
            $maxK = (int) ($request->max_k ?? 10);

            Log::debug('RFM Elbow: Fetching raw data', ['from' => $from, 'to' => $to]);

            $rawData = $this->getRawDataForElbow($from, $to);
            if ($rawData->isEmpty()) {
                Log::warning('RFM Elbow: No transaction data found', ['user_id' => $userId, 'from' => $from, 'to' => $to]);
                return response()->json(['error' => 'Tidak ada data transaksi.'], 422);
            }

            [$normalized] = $this->normalizeDataForElbow($rawData);
            $points = $normalized->map(fn($row) => [
                $row->recency_norm,
                $row->frequency_norm,
                $row->monetary_norm,
            ])->toArray();

            Log::debug('RFM Elbow: Calling service calculateElbow', ['max_k' => $maxK, 'points_count' => count($points)]);
            $inertias = $this->rfmService->calculateElbow($points, $maxK);

            Log::info('RFM Elbow: Success', [
                'user_id' => $userId,
                'max_k'   => $maxK,
                'from'    => $from->toDateString(),
                'to'      => $to->toDateString(),
                'inertias'=> $inertias,
            ]);

            return response()->json([
                'from'      => $from->toDateString(),
                'to'        => $to->toDateString(),
                'max_k'     => $maxK,
                'inertias'  => $inertias,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Elbow: Exception', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan server', 'error' => $e->getMessage()], 500);
        }
    }

    public function dbiComparison(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM DBI Comparison: Start', ['user_id' => $userId, 'input' => $request->only('from', 'to', 'max_k')]);

        $validator = validator($request->all(), [
            'from'  => ['nullable', 'date'],
            'to'    => ['nullable', 'date', 'after_or_equal:from'],
            'max_k' => ['nullable', 'integer', 'min:2', 'max:15'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM DBI Comparison: Validation failed', ['user_id' => $userId, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->rfmService->calculateDbiComparison(
                userId: $userId,
                maxK:   (int) ($request->max_k ?? 10),
                from:   $request->from ? Carbon::parse($request->from) : null,
                to:     $request->to   ? Carbon::parse($request->to)   : null,
            );

            Log::info('RFM DBI Comparison: Success', [
                'user_id' => $userId,
                'result'  => $result,
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('RFM DBI Comparison: Exception', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan server', 'error' => $e->getMessage()], 500);
        }
    }

    public function batches(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $perPage = $request->per_page ?? 15;
        Log::info('RFM Batches: List', ['user_id' => $userId, 'per_page' => $perPage]);

        try {
            $batches = RfmCalculationBatch::with(['triggeredBy:id,name', 'dbiScore'])
                ->latest()
                ->paginate($perPage);

            Log::debug('RFM Batches: Retrieved', ['user_id' => $userId, 'total' => $batches->total()]);
            return response()->json($batches);
        } catch (\Exception $e) {
            Log::error('RFM Batches: Exception', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function batchDetail(RfmCalculationBatch $batch, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Batch Detail', ['user_id' => $userId, 'batch_id' => $batch->id]);

        try {
            $batch->load(['triggeredBy:id,name', 'dbiScore']);
            Log::debug('RFM Batch Detail: Loaded', ['batch_id' => $batch->id, 'status' => $batch->status]);
            return response()->json([
                'batch' => $batch,
                'dbi'   => $batch->dbiScore,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Batch Detail: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function rawData(Request $request, RfmCalculationBatch $batch): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Raw Data', ['user_id' => $userId, 'batch_id' => $batch->id, 'params' => $request->only('sort_by', 'sort_order', 'per_page', 'search')]);

        $validator = validator($request->all(), [
            'sort_by'    => ['nullable', 'in:recency_days,frequency,monetary'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page'   => ['nullable', 'integer', 'min:5', 'max:200'],
            'search'     => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM Raw Data: Validation failed', ['user_id' => $userId, 'batch_id' => $batch->id, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $query = RfmCustomerRaw::where('calculation_batch_id', $batch->id)
                ->with('customer:id,name,email');

            if ($request->search) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            $sortBy    = $request->sort_by ?? 'customer_id';
            $sortOrder = $request->sort_order ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            $data = $query->paginate($request->per_page ?? 20);

            $stats = RfmCustomerRaw::where('calculation_batch_id', $batch->id)
                ->selectRaw('
                    MIN(recency_days) as min_recency, MAX(recency_days) as max_recency, AVG(recency_days) as avg_recency,
                    MIN(frequency)    as min_frequency, MAX(frequency)   as max_frequency, AVG(frequency)   as avg_frequency,
                    MIN(monetary)     as min_monetary, MAX(monetary)     as max_monetary, AVG(monetary)     as avg_monetary
                ')
                ->first();

            Log::debug('RFM Raw Data: Retrieved', [
                'user_id'    => $userId,
                'batch_id'   => $batch->id,
                'total_rows' => $data->total(),
                'stats'      => [
                    'recency'   => ['min' => $stats->min_recency,   'max' => $stats->max_recency],
                    'frequency' => ['min' => $stats->min_frequency, 'max' => $stats->max_frequency],
                    'monetary'  => ['min' => $stats->min_monetary,  'max' => $stats->max_monetary],
                ]
            ]);

            return response()->json([
                'data'  => $data,
                'stats' => [
                    'recency'   => ['min' => $stats->min_recency,   'max' => $stats->max_recency,   'avg' => round($stats->avg_recency, 1)],
                    'frequency' => ['min' => $stats->min_frequency, 'max' => $stats->max_frequency, 'avg' => round($stats->avg_frequency, 1)],
                    'monetary'  => ['min' => $stats->min_monetary,  'max' => $stats->max_monetary,  'avg' => round($stats->avg_monetary, 2)],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Raw Data: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function normalizedData(Request $request, RfmCalculationBatch $batch): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Normalized Data', ['user_id' => $userId, 'batch_id' => $batch->id, 'params' => $request->only('sort_by', 'sort_order', 'per_page', 'search')]);

        $validator = validator($request->all(), [
            'sort_by'    => ['nullable', 'in:recency_norm,frequency_norm,monetary_norm'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page'   => ['nullable', 'integer', 'min:5', 'max:200'],
            'search'     => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM Normalized Data: Validation failed', ['user_id' => $userId, 'batch_id' => $batch->id, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $query = RfmCustomerNormalized::where('calculation_batch_id', $batch->id)
                ->with('customer:id,name,email');

            if ($request->search) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            $sortBy    = $request->sort_by ?? 'customer_id';
            $sortOrder = $request->sort_order ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            $data = $query->paginate($request->per_page ?? 20);

            Log::debug('RFM Normalized Data: Retrieved', [
                'user_id'    => $userId,
                'batch_id'   => $batch->id,
                'total_rows' => $data->total(),
            ]);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('RFM Normalized Data: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function iterations(RfmCalculationBatch $batch, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Iterations', ['user_id' => $userId, 'batch_id' => $batch->id]);

        try {
            $iterations = RfmKmeansIteration::where('calculation_batch_id', $batch->id)
                ->orderBy('iteration_number')
                ->get();

            Log::debug('RFM Iterations: Retrieved', [
                'user_id'   => $userId,
                'batch_id'  => $batch->id,
                'iterations'=> $iterations->count(),
            ]);

            return response()->json([
                'batch'      => [
                    'k_clusters'        => $batch->k_clusters,
                    'actual_iterations' => $batch->actual_iterations,
                    'inertia'           => $batch->inertia,
                ],
                'iterations' => $iterations,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Iterations: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function centroids(Request $request, RfmCalculationBatch $batch): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Centroids', ['user_id' => $userId, 'batch_id' => $batch->id, 'params' => $request->only('iteration', 'cluster')]);

        $validator = validator($request->all(), [
            'iteration' => ['nullable', 'integer', 'min:0'],
            'cluster'   => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            Log::warning('RFM Centroids: Validation failed', ['user_id' => $userId, 'batch_id' => $batch->id, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
        }

        try {
            $query = RfmCentroid::where('calculation_batch_id', $batch->id)
                ->orderBy('iteration_number')
                ->orderBy('cluster_id');

            if ($request->has('iteration')) {
                $query->where('iteration_number', $request->iteration);
            }

            if ($request->has('cluster')) {
                $query->where('cluster_id', $request->cluster);
            }

            $centroids = $query->get();

            $grouped = $centroids->groupBy('iteration_number')->map(function ($rows, $iterNum) {
                return [
                    'iteration_number' => $iterNum,
                    'clusters'         => $rows->map(fn($r) => [
                        'cluster_id'    => $r->cluster_id,
                        'cluster_label' => 'C' . ($r->cluster_id + 1),
                        'recency_pos'   => $r->recency_pos,
                        'frequency_pos' => $r->frequency_pos,
                        'monetary_pos'  => $r->monetary_pos,
                    ])->values(),
                ];
            })->values();

            Log::debug('RFM Centroids: Retrieved', [
                'user_id'            => $userId,
                'batch_id'           => $batch->id,
                'iterations_found'   => $grouped->count(),
            ]);

            return response()->json([
                'batch_id'   => $batch->id,
                'k_clusters' => $batch->k_clusters,
                'centroids'  => $grouped,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Centroids: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignments(Request $request, RfmCalculationBatch $batch): JsonResponse
{
    $userId = $request->user()?->id;
    Log::info('RFM Assignments', ['user_id' => $userId, 'batch_id' => $batch->id, 'params' => $request->only('iteration', 'cluster_id', 'per_page', 'search')]);

    $validator = validator($request->all(), [
        'iteration'  => ['required', 'integer', 'min:1'],
        'cluster_id' => ['nullable', 'integer', 'min:0'],
        'per_page'   => ['nullable', 'integer', 'min:5', 'max:200'],
        'search'     => ['nullable', 'string', 'max:100'],
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
    }

    try {
        // Ambil data normalisasi untuk semua customer dalam batch ini
        $normalizedData = RfmCustomerNormalized::where('calculation_batch_id', $batch->id)
            ->select('customer_id', 'recency_norm', 'frequency_norm', 'monetary_norm')
            ->get()
            ->keyBy('customer_id');

        $query = RfmKmeansAssignment::where('calculation_batch_id', $batch->id)
            ->where('iteration_number', $request->iteration)
            ->with('customer:id,name,email');

        if ($request->has('cluster_id')) {
            $query->where('cluster_id', $request->cluster_id);
        }

        if ($request->search) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $sortBy    = $request->sort_by ?? 'customer_id';
        $sortOrder = $request->sort_order ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $data = $query->paginate($request->per_page ?? 20);

        // Tambahkan nilai normalisasi ke setiap item
        $data->getCollection()->transform(function ($item) use ($normalizedData) {
            $norm = $normalizedData->get($item->customer_id);
            if ($norm) {
                $item->recency_norm = $norm->recency_norm;
                $item->frequency_norm = $norm->frequency_norm;
                $item->monetary_norm = $norm->monetary_norm;
            } else {
                $item->recency_norm = null;
                $item->frequency_norm = null;
                $item->monetary_norm = null;
            }
            // Pastikan distances_to_all_centroids dikonversi ke array jika string
            if (is_string($item->distances_to_all_centroids)) {
                $item->distances_to_all_centroids = json_decode($item->distances_to_all_centroids, true);
            }
            return $item;
        });

        // Ambil centroid untuk iterasi sebelumnya (karena iteration_number pada assignment adalah hasil setelah update centroid)
        // Sesuai logic Anda, centroid untuk assignment iterasi ke-n adalah centroid dari iterasi ke-(n-1)
        $centroids = RfmCentroid::where('calculation_batch_id', $batch->id)
            ->where('iteration_number', $request->iteration - 1)
            ->orderBy('cluster_id')
            ->get(['cluster_id', 'recency_pos', 'frequency_pos', 'monetary_pos']);

        $iterStats = RfmKmeansIteration::where('calculation_batch_id', $batch->id)
            ->where('iteration_number', $request->iteration)
            ->first(['wcss', 'assignments_changed', 'cluster_sizes', 'is_converged']);

        return response()->json([
            'data'       => $data,
            'centroids'  => $centroids,
            'iter_stats' => $iterStats,
        ]);
    } catch (\Exception $e) {
        Log::error('RFM Assignments: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
        return response()->json(['message' => 'Terjadi kesalahan server', 'error' => $e->getMessage()], 500);
    }
}

    // app/Http/Controllers/RFM/RfmController.php

public function showBatchDetail($batchId)
{
    Log::info('WEB: batch.detail accessed', [
        'user_id'  => auth()->id(),
        'batch_id' => $batchId,
    ]);

    $batch = RfmCalculationBatch::find($batchId);
    if (!$batch) {
        abort(404, 'Batch tidak ditemukan');
    }

    return view('modules.rfm.batch-detail', [
        'batchId' => $batchId,
        'batch'   => $batch, // optional, kirim data ke blade
    ]);
}

public function showDashboard()
{
    

    // Data dikirim ke view
    return view('modules.rfm.index');
}

public function showAbout()
{
    return view('modules.rfm.rfm');
}

public function showCalculateForm()
{
    return view('modules.rfm.calculate');
}

public function showCustomerHistory($customerId)
{
    $customer = Customer::find($customerId);
    if (!$customer) {
        abort(404, 'Pelanggan tidak ditemukan');
    }
    return view('modules.rfm.customer-history', compact('customerId'));
}

    public function scores(Request $request, RfmCalculationBatch $batch): JsonResponse
{
    $validator = validator($request->all(), [
        'cluster_id' => ['nullable', 'integer', 'min:0'],
        'segment'    => ['nullable', 'string'],
        'sort_by'    => ['nullable', 'in:rfm_score,distance_to_centroid,recency_days,frequency,monetary'],
        'sort_order' => ['nullable', 'in:asc,desc'],
        'per_page'   => ['nullable', 'integer', 'min:5', 'max:200'],
        'search'     => ['nullable', 'string', 'max:100'],
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Data tidak valid', 'errors' => $validator->errors()], 422);
    }

    try {
        $query = RfmScore::where('calculation_batch_id', $batch->id)
            ->with('customer:id,name,email');

        if ($request->has('cluster_id')) {
            $query->where('cluster_id', $request->cluster_id);
        }

        if ($request->segment) {
            $query->where('segment_name', $request->segment);
        }

        if ($request->search) {
            $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%"));
        }

        $sortBy    = $request->sort_by ?? 'cluster_id';
        $sortOrder = $request->sort_order ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $data = $query->paginate($request->per_page ?? 20);

        // Ringkasan per cluster (tidak dipengaruhi filter halaman)
        $summary = RfmScore::where('calculation_batch_id', $batch->id)
            ->selectRaw('cluster_id, segment_name, COUNT(*) as total')
            ->groupBy('cluster_id', 'segment_name')
            ->orderBy('cluster_id')
            ->get()
            ->map(fn($r) => [
                'cluster_id'   => $r->cluster_id,
                'cluster_label'=> 'C' . ($r->cluster_id + 1),
                'segment_name' => $r->segment_name,
                'total'        => $r->total,
            ]);

        return response()->json([
            'data'    => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'per_page'     => $data->perPage(),
            'total'        => $data->total(),
            'links'        => [
                'next' => $data->nextPageUrl(),
                'prev' => $data->previousPageUrl(),
            ],
            'summary'   => $summary,
            'batch_info'=> [
                'cluster_labels' => $batch->cluster_labels,
                'k_clusters'     => $batch->k_clusters,
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Scores error', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);
        return response()->json(['data' => [], 'summary' => [], 'links' => ['next' => null, 'prev' => null], 'total' => 0], 200);
    }
}

    public function scatterData(RfmCalculationBatch $batch, Request $request): JsonResponse
{
    try {
        $scores = RfmScore::where('calculation_batch_id', $batch->id)
            ->with('customer:id,name')
            ->get();

        if ($scores->isEmpty()) {
            return response()->json([
                'points' => [],
                'cluster_labels' => $batch->cluster_labels ?? [],
                'message' => 'Tidak ada data score untuk batch ini'
            ]);
        }

        $points = $scores->map(fn($r) => [
            'customer_id'   => $r->customer_id,
            'name'          => $r->customer->name ?? '-',
            'R'             => round($r->recency_norm, 6),
            'F'             => round($r->frequency_norm, 6),
            'M'             => round($r->monetary_norm, 6),
            'cluster_id'    => $r->cluster_id,
            'cluster_label' => 'C' . ($r->cluster_id + 1),
            'segment_name'  => $r->segment_name,
            'rfm_score'     => $r->rfm_score,
        ]);

        return response()->json([
            'points' => $points,
            'cluster_labels' => $batch->cluster_labels ?? [],
        ]);
    } catch (\Exception $e) {
        Log::error('RFM Scatter Data Error', [
            'batch_id' => $batch->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Gagal memuat data scatter',
            'message' => $e->getMessage(),
            'points' => [],
            'cluster_labels' => []
        ], 500); // tetap 500 agar JavaScript bisa menangani
    }
}

    public function dbi(RfmCalculationBatch $batch, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM DBI Detail', ['user_id' => $userId, 'batch_id' => $batch->id]);

        try {
            $dbi = RfmDbiScore::where('calculation_batch_id', $batch->id)->firstOrFail();

            $interpretation = $dbi->dbi_score < 0.5
                ? 'Excellent — cluster sangat terpisah'
                : ($dbi->dbi_score < 1.0
                    ? 'Good — cluster cukup terpisah'
                    : 'Fair — cluster kurang terpisah');

            Log::debug('RFM DBI Detail: Retrieved', [
                'user_id'   => $userId,
                'batch_id'  => $batch->id,
                'dbi_score' => $dbi->dbi_score,
            ]);

            return response()->json([
                'k'               => $dbi->k,
                'dbi_score'       => $dbi->dbi_score,
                'cluster_details' => $dbi->cluster_details,
                'interpretation'  => $interpretation,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM DBI Detail: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function customerHistory(int $customerId, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Customer History', ['user_id' => $userId, 'customer_id' => $customerId]);

        try {
            $history = RfmSegmentHistory::where('customer_id', $customerId)
                ->with('calculationBatch:id,data_from,data_to,k_clusters')
                ->orderByDesc('created_at')
                ->get();

            Log::debug('RFM Customer History: Retrieved', [
                'user_id'     => $userId,
                'customer_id' => $customerId,
                'records'     => $history->count(),
            ]);

            return response()->json([
                'customer_id' => $customerId,
                'history'     => $history,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Customer History: Exception', ['user_id' => $userId, 'customer_id' => $customerId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function segmentHistory(RfmCalculationBatch $batch, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        Log::info('RFM Segment History', ['user_id' => $userId, 'batch_id' => $batch->id]);

        try {
            $history = RfmSegmentHistory::where('calculation_batch_id', $batch->id)
                ->with('customer:id,name,email')
                ->orderBy('customer_id')
                ->paginate(20);

            $changed = RfmSegmentHistory::where('calculation_batch_id', $batch->id)
                ->where('is_segment_changed', true)
                ->count();

            Log::debug('RFM Segment History: Retrieved', [
                'user_id'       => $userId,
                'batch_id'      => $batch->id,
                'total'         => $history->total(),
                'changed'       => $changed,
                'unchanged'     => $batch->total_customers - $changed,
            ]);

            return response()->json([
                'data'              => $history,
                'total_changed'     => $changed,
                'total_unchanged'   => $batch->total_customers - $changed,
            ]);
        } catch (\Exception $e) {
            Log::error('RFM Segment History: Exception', ['user_id' => $userId, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers (with logging)
    // -------------------------------------------------------------------------

    private function getRawDataForElbow(Carbon $from, Carbon $to)
    {
        $referenceDate = $to;
        Log::debug('getRawDataForElbow: Querying', ['from' => $from, 'to' => $to]);

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
                COALESCE(DATEDIFF(?, MAX(t.transaction_date)), 999999) AS recency_days,
                COALESCE(COUNT(t.id), 0) AS frequency,
                COALESCE(SUM(t.total_price), 0) AS monetary
            ", [$referenceDate])
            ->get();

        $maxRecency = $rawData->max('recency_days');
        if ($maxRecency == 999999) {
            $earliestPossible = $from->copy()->subYears(10);
            $defaultRecency = $referenceDate->diffInDays($earliestPossible);
            Log::debug('getRawDataForElbow: Fixing recency 999999', [
                'default_recency' => $defaultRecency,
                'affected_rows'   => $rawData->where('recency_days', 999999)->count(),
            ]);
            $rawData = $rawData->map(function($row) use ($defaultRecency) {
                if ($row->recency_days == 999999) $row->recency_days = $defaultRecency;
                return $row;
            });
        }

        Log::debug('getRawDataForElbow: Retrieved', ['rows' => $rawData->count()]);
        return $rawData;
    }

    private function normalizeDataForElbow($rawData)
    {
        Log::debug('normalizeDataForElbow: Starting normalization', ['rows' => $rawData->count()]);

        $rVals = $rawData->pluck('recency_days');
        $fVals = $rawData->pluck('frequency');
        $mVals = $rawData->pluck('monetary');

        $normStats = [
            'recency'   => ['min' => $rVals->min(), 'max' => $rVals->max()],
            'frequency' => ['min' => $fVals->min(), 'max' => $fVals->max()],
            'monetary'  => ['min' => $mVals->min(), 'max' => $mVals->max()],
        ];

        Log::debug('normalizeDataForElbow: Normalization stats', $normStats);

        $normalized = $rawData->map(function ($row) use ($normStats) {
            $recencyNorm = ($normStats['recency']['max'] == $normStats['recency']['min'])
                ? 0.5
                : 1 - (($row->recency_days - $normStats['recency']['min']) / ($normStats['recency']['max'] - $normStats['recency']['min']));
            $frequencyNorm = ($normStats['frequency']['max'] == $normStats['frequency']['min'])
                ? 0.5
                : ($row->frequency - $normStats['frequency']['min']) / ($normStats['frequency']['max'] - $normStats['frequency']['min']);
            $monetaryNorm = ($normStats['monetary']['max'] == $normStats['monetary']['min'])
                ? 0.5
                : ($row->monetary - $normStats['monetary']['min']) / ($normStats['monetary']['max'] - $normStats['monetary']['min']);
            return (object) [
                'customer_id'    => $row->customer_id,
                'recency_norm'   => round($recencyNorm, 6),
                'frequency_norm' => round($frequencyNorm, 6),
                'monetary_norm'  => round($monetaryNorm, 6),
            ];
        });

        Log::debug('normalizeDataForElbow: Completed', ['rows' => $normalized->count()]);
        return [$normalized, $normStats];
    }
}