<?php

namespace App\Http\Controllers\RFM;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\RfmCalculationBatch;
use App\Models\RfmScore;
use App\Models\RfmSegmentHistory;
use App\Services\RfmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
    public function __construct(private RfmService $rfmService)
    {
        //
    }

    // =========================================================================
    // Logger helpers
    // =========================================================================

    private function dbg(string $action, string $message, array $context = []): void
    {
        Log::channel('rfm')->debug("[RFM][Controller][{$action}] {$message}", $context);
    }

    private function info(string $action, string $message, array $context = []): void
    {
        Log::channel('rfm')->info("[RFM][Controller][{$action}] {$message}", $context);
    }

    private function warn(string $action, string $message, array $context = []): void
    {
        Log::channel('rfm')->warning("[RFM][Controller][{$action}] {$message}", $context);
    }

    private function err(string $action, string $message, array $context = []): void
    {
        Log::channel('rfm')->error("[RFM][Controller][{$action}] {$message}", $context);
    }

    private function timer(): float { return microtime(true); }
    private function elapsed(float $start): string
    {
        return round((microtime(true) - $start) * 1000, 2) . ' ms';
    }


    // =========================================================================
    // index()
    // =========================================================================

    /**
     * Halaman utama analisis RFM: tabel skor, scatter plot, ringkasan segmen
     */
    public function index(Request $request)
    {
        $t = $this->timer();
        $this->info('index', 'Halaman index dibuka', [
            'user_id' => Auth::id(),
            'filters' => $request->only(['segment', 'search']),
        ]);

        // Ambil batch terbaru yang completed
        $latestBatch = RfmCalculationBatch::where('status', 'completed')
            ->latest()
            ->first();

        if (!$latestBatch) {
            $this->warn('index', 'Tidak ada batch completed — halaman akan tampil kosong');
        } else {
            $this->dbg('index', 'Latest batch ditemukan', [
                'batch_id'        => $latestBatch->id,
                'k_clusters'      => $latestBatch->k_clusters,
                'total_customers' => $latestBatch->total_customers,
                'created_at'      => $latestBatch->created_at?->toDateTimeString(),
            ]);
        }

        $scores       = collect();
        $segmentStats = collect();
        $batchList    = RfmCalculationBatch::with('triggeredBy')
            ->latest()
            ->paginate(10, pageName: 'batch_page');

        $this->dbg('index', 'Batch list diambil', ['total_batches' => $batchList->total()]);

        if ($latestBatch) {
            $tScores = $this->timer();
            $scores = RfmScore::with('customer')
                ->where('calculation_batch_id', $latestBatch->id)
                ->when($request->segment, fn($q) => $q->where('segment_name', $request->segment))
                ->when($request->search, fn($q) => $q->whereHas('customer', fn($cq) =>
                    $cq->where('name', 'like', '%'.$request->search.'%')
                       ->orWhere('email', 'like', '%'.$request->search.'%')
                ))
                ->orderByDesc('rfm_score')
                ->paginate(20);

            $this->dbg('index', 'RFM scores diambil', [
                'total'   => $scores->total(),
                'page'    => $scores->currentPage(),
                'filter'  => $request->only(['segment', 'search']),
                'elapsed' => $this->elapsed($tScores),
            ]);

            $tSeg = $this->timer();
            $segmentStats = RfmScore::where('calculation_batch_id', $latestBatch->id)
                ->selectRaw("
                    segment_name,
                    cluster_id,
                    COUNT(*)                    AS total,
                    ROUND(AVG(rfm_score), 2)    AS avg_rfm,
                    ROUND(AVG(recency_days), 1) AS avg_recency,
                    ROUND(AVG(frequency), 1)    AS avg_frequency,
                    ROUND(AVG(monetary), 2)     AS avg_monetary
                ")
                ->groupBy('segment_name', 'cluster_id')
                ->orderBy('cluster_id')
                ->get();

            $this->dbg('index', 'Segment stats diambil', [
                'segment_count' => $segmentStats->count(),
                'segments'      => $segmentStats->pluck('total', 'segment_name')->toArray(),
                'elapsed'       => $this->elapsed($tSeg),
            ]);
        }

        // Scatter data
        $tScatter = $this->timer();
        $scatterData = $latestBatch
            ? RfmScore::where('calculation_batch_id', $latestBatch->id)
                ->select('recency_norm', 'frequency_norm', 'monetary_norm', 'segment_name', 'cluster_id', 'customer_id')
                ->get()
            : collect();

        $this->dbg('index', 'Scatter data diambil', [
            'count'   => $scatterData->count(),
            'elapsed' => $this->elapsed($tScatter),
        ]);

        $this->info('index', 'index() selesai dirender', [
            'total_elapsed' => $this->elapsed($t),
        ]);

        return view('modules.rfm.index', compact(
            'latestBatch', 'scores', 'segmentStats', 'batchList', 'scatterData'
        ));
    }


    // =========================================================================
    // create()
    // =========================================================================

    /**
     * Halaman hitung ulang: form setting K dan rentang tanggal
     */
    public function create()
    {
        $this->info('create', 'Halaman form kalkulasi dibuka', ['user_id' => Auth::id()]);

        $lastBatch = RfmCalculationBatch::where('status', 'completed')->latest()->first();

        $this->dbg('create', 'Last completed batch', [
            'batch_id' => $lastBatch?->id,
            'k'        => $lastBatch?->k_clusters,
        ]);

        return view('modules.rfm.calculate', compact('lastBatch'));
    }


    // =========================================================================
    // store()
    // =========================================================================

    /**
     * Jalankan kalkulasi RFM + K-Means
     */
    public function store(Request $request)
    {
        $t = $this->timer();
        $this->info('store', 'Request kalkulasi masuk', [
            'user_id'    => Auth::id(),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload'    => $request->only(['k_clusters', 'date_from', 'date_to']),
        ]);

        $validated = $request->validate([
            'k_clusters' => 'required|integer|min:2|max:10',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $this->dbg('store', 'Validasi lolos', $validated);

        $from = $validated['date_from'] ? Carbon::parse($validated['date_from']) : null;
        $to   = $validated['date_to']   ? Carbon::parse($validated['date_to'])   : null;

        $this->dbg('store', 'Parsing tanggal selesai', [
            'from' => $from?->toDateString() ?? '(default: -2 tahun)',
            'to'   => $to?->toDateString()   ?? '(default: sekarang)',
        ]);

        try {
            $result = $this->rfmService->calculate(
                userId:    Auth::user()->id,
                kClusters: $validated['k_clusters'],
                from:      $from,
                to:        $to,
            );

            $this->info('store', 'Kalkulasi selesai', [
                'batch_id'   => $result['batch']->id,
                'success'    => $result['success'],
                'step_count' => count($result['steps']),
                'elapsed'    => $this->elapsed($t),
            ]);

            if (!$result['success']) {
                $this->err('store', 'Kalkulasi gagal (service return success=false)', [
                    'error'    => $result['error'] ?? 'unknown',
                    'batch_id' => $result['batch']->id,
                ]);
            }

        } catch (\Throwable $e) {
            $this->err('store', 'Exception tak tertangkap saat memanggil RfmService::calculate()', [
                'error'   => $e->getMessage(),
                'class'   => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => collect(explode("\n", $e->getTraceAsString()))->take(10)->toArray(),
                'elapsed' => $this->elapsed($t),
            ]);
            throw $e;
        }

        return redirect()
            ->route('rfm.batch.show', $result['batch']->id)
            ->with('calculation_steps', $result['steps'])
            ->with('success', $result['success']);
    }


    // =========================================================================
    // showBatch()
    // =========================================================================

    /**
     * Detail satu batch: tampilkan step-by-step perhitungan
     */
    public function showBatch(RfmCalculationBatch $batch)
    {
        $t = $this->timer();
        $this->info('showBatch', 'Halaman detail batch dibuka', [
            'batch_id' => $batch->id,
            'status'   => $batch->status,
            'user_id'  => Auth::id(),
        ]);

        $batch->load('triggeredBy');
        $steps = session('calculation_steps', []);

        if (empty($steps)) {
            $this->warn('showBatch', 'Session calculation_steps kosong — rebuild dari batch record', [
                'batch_id' => $batch->id,
            ]);
            $steps = $this->rebuildBatchSummary($batch);
        } else {
            $this->dbg('showBatch', 'Steps diambil dari session', ['step_count' => count($steps)]);
        }

        $tScores = $this->timer();
        $scores = RfmScore::with('customer')
            ->where('calculation_batch_id', $batch->id)
            ->orderByDesc('rfm_score')
            ->get();

        $this->dbg('showBatch', 'Scores diambil', [
            'count'   => $scores->count(),
            'elapsed' => $this->elapsed($tScores),
        ]);

        $centroids     = $batch->final_centroids ?? [];
        $clusterLabels = $batch->cluster_labels  ?? [];

        $this->dbg('showBatch', 'Centroid & label dari batch', [
            'centroid_count' => count($centroids),
            'labels'         => $clusterLabels,
        ]);

        $this->info('showBatch', 'showBatch() selesai dirender', [
            'batch_id'      => $batch->id,
            'total_elapsed' => $this->elapsed($t),
        ]);

        return view('modules.rfm.batch-detail', compact('batch', 'steps', 'scores', 'centroids', 'clusterLabels'));
    }


    // =========================================================================
    // updateClusterLabels()
    // =========================================================================

    /**
     * Update label cluster (admin edit manual)
     */
    public function updateClusterLabels(Request $request, RfmCalculationBatch $batch)
    {
        $t = $this->timer();
        $this->info('updateClusterLabels', 'Request update label masuk', [
            'batch_id' => $batch->id,
            'user_id'  => Auth::id(),
            'payload'  => $request->get('labels'),
        ]);

        $validated = $request->validate([
            'labels'   => 'required|array',
            'labels.*' => 'required|string|max:50',
        ]);

        $this->dbg('updateClusterLabels', 'Label yang akan disimpan', ['labels' => $validated['labels']]);

        $batch->update(['cluster_labels' => $validated['labels']]);

        $updatedTotal = 0;
        foreach ($validated['labels'] as $clusterId => $label) {
            $affected = RfmScore::where('calculation_batch_id', $batch->id)
                ->where('cluster_id', (int) $clusterId)
                ->update(['segment_name' => $label]);

            $this->dbg('updateClusterLabels', "Update cluster #{$clusterId} → [{$label}]", [
                'rows_affected' => $affected,
            ]);
            $updatedTotal += $affected;
        }

        $this->info('updateClusterLabels', 'Update label selesai', [
            'batch_id'      => $batch->id,
            'total_updated' => $updatedTotal,
            'elapsed'       => $this->elapsed($t),
        ]);

        return back()->with('toast', 'Label segmen berhasil diperbarui.');
    }


    // =========================================================================
    // customerHistory()
    // =========================================================================

    /**
     * Halaman histori segmen satu pelanggan
     */
    public function customerHistory(int $customerId)
    {
        $t = $this->timer();
        $this->info('customerHistory', 'Halaman histori customer dibuka', [
            'customer_id' => $customerId,
            'user_id'     => Auth::id(),
        ]);

        $history = RfmSegmentHistory::with(['calculationBatch.triggeredBy', 'rfmScore'])
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate(15);

        $this->dbg('customerHistory', 'Histori customer diambil', [
            'customer_id'   => $customerId,
            'total_records' => $history->total(),
            'elapsed'       => $this->elapsed($t),
        ]);

        $customer = Customers::findOrFail($customerId);

        $this->dbg('customerHistory', 'Data customer', [
            'name'  => $customer->name,
            'email' => $customer->email,
        ]);

        return view('modules.rfm.customer-history', compact('customer', 'history'));
    }


    // =========================================================================
    // scatterData() — JSON API
    // =========================================================================

    /**
     * API: data scatter plot untuk chart.js (JSON)
     */
    public function scatterData(RfmCalculationBatch $batch)
    {
        $t = $this->timer();
        $this->info('scatterData', 'API scatter data diminta', [
            'batch_id' => $batch->id,
            'user_id'  => Auth::id(),
        ]);

        $data = RfmScore::where('calculation_batch_id', $batch->id)
            ->select(
                'recency_norm', 'frequency_norm', 'monetary_norm',
                'segment_name', 'cluster_id', 'rfm_score', 'customer_id',
                'recency_days', 'frequency', 'monetary'
            )
            ->with('customer:id,name')
            ->get();

        $this->dbg('scatterData', 'Data scatter diambil', [
            'count'   => $data->count(),
            'elapsed' => $this->elapsed($t),
        ]);

        return response()->json($data);
    }


    // =========================================================================
    // elbowData() — JSON API
    // =========================================================================

    /**
     * API: elbow method — hitung SSE untuk K = 2..max_k
     */
    public function elbowData(Request $request)
    {
        $t = $this->timer();
        $maxK = min((int) $request->get('max_k', 8), 10);

        $this->info('elbowData', 'API elbow data diminta', [
            'max_k'   => $maxK,
            'user_id' => Auth::id(),
        ]);

        $latestBatch = RfmCalculationBatch::where('status', 'completed')->latest()->first();

        if (!$latestBatch) {
            $this->warn('elbowData', 'Tidak ada batch completed — return array kosong');
            return response()->json([]);
        }

        $this->dbg('elbowData', 'Menggunakan batch terbaru untuk elbow', [
            'batch_id'        => $latestBatch->id,
            'total_customers' => $latestBatch->total_customers,
        ]);

        $tScores = $this->timer();
        $scores = RfmScore::where('calculation_batch_id', $latestBatch->id)
            ->select('recency_norm', 'frequency_norm', 'monetary_norm')
            ->get()
            ->toArray();

        $this->dbg('elbowData', 'Data normalized diambil untuk elbow', [
            'count'   => count($scores),
            'elapsed' => $this->elapsed($tScores),
        ]);

        $elbowData = [];
        for ($k = 2; $k <= $maxK; $k++) {
            $tK  = $this->timer();
            $sse = $this->rfmService->calculateSseForK($scores, $k);
            $elbowData[] = ['k' => $k, 'sse' => round($sse, 4)];

            $this->dbg('elbowData', "K={$k} SSE dihitung", [
                'sse'     => round($sse, 4),
                'elapsed' => $this->elapsed($tK),
            ]);
        }

        $this->info('elbowData', 'Elbow data selesai', [
            'max_k'         => $maxK,
            'results'       => $elbowData,
            'total_elapsed' => $this->elapsed($t),
        ]);

        return response()->json($elbowData);
    }


    // =========================================================================
    // Private helper
    // =========================================================================

    private function rebuildBatchSummary(RfmCalculationBatch $batch): array
    {
        $this->dbg('rebuildBatchSummary', 'Rebuild summary dari batch record', ['batch_id' => $batch->id]);

        $scores = RfmScore::where('calculation_batch_id', $batch->id)->get();

        $this->dbg('rebuildBatchSummary', 'Scores diambil untuk summary', ['count' => $scores->count()]);

        return [
            [
                'step'        => 1,
                'title'       => 'Pengambilan data transaksi',
                'stats'       => ['count' => $batch->total_customers],
                'description' => "Batch ini menghitung {$batch->total_customers} pelanggan.",
            ],
            [
                'step'        => 4,
                'title'       => "K-Means (K={$batch->k_clusters})",
                'iterations'  => $batch->actual_iterations,
                'inertia'     => $batch->inertia,
                'description' => "Konvergen setelah {$batch->actual_iterations} iterasi. SSE = {$batch->inertia}.",
            ],
            [
                'step'        => 6,
                'title'       => 'Tersimpan ke database',
                'stats'       => ['saved' => $scores->count()],
                'description' => "Total {$scores->count()} skor RFM tersimpan.",
            ],
        ];
    }
}