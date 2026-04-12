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

class RfmController extends Controller
{
     public function __construct(private RfmService $rfmService)
    {
        
    }
 
    /**
     * Halaman utama analisis RFM: tabel skor, scatter plot, ringkasan segmen
     */
    public function index(Request $request)
    {
        // Ambil batch terbaru yang completed
        $latestBatch = RfmCalculationBatch::where('status', 'completed')
            ->latest()
            ->first();
 
        $scores       = collect();
        $segmentStats = collect();
        $batchList    = RfmCalculationBatch::with('triggeredBy')
            ->latest()
            ->paginate(10, pageName: 'batch_page');
 
        if ($latestBatch) {
            $scores = RfmScore::with('customer')
                ->where('calculation_batch_id', $latestBatch->id)
                ->when($request->segment, fn($q) => $q->where('segment_name', $request->segment))
                ->when($request->search, fn($q) => $q->whereHas('customer', fn($cq) =>
                    $cq->where('name', 'like', '%'.$request->search.'%')
                       ->orWhere('email', 'like', '%'.$request->search.'%')
                ))
                ->orderByDesc('rfm_score')
                ->paginate(20);
 
            $segmentStats = RfmScore::where('calculation_batch_id', $latestBatch->id)
                ->selectRaw("
                    segment_name,
                    cluster_id,
                    COUNT(*)            AS total,
                    ROUND(AVG(rfm_score), 2)  AS avg_rfm,
                    ROUND(AVG(recency_days), 1) AS avg_recency,
                    ROUND(AVG(frequency), 1)    AS avg_frequency,
                    ROUND(AVG(monetary), 2)     AS avg_monetary
                ")
                ->groupBy('segment_name', 'cluster_id')
                ->orderBy('cluster_id')
                ->get();
        }
 
        // Data untuk scatter plot (lightweight: hanya ambil 3 kolom norm)
        $scatterData = $latestBatch
            ? RfmScore::where('calculation_batch_id', $latestBatch->id)
                ->select('recency_norm', 'frequency_norm', 'monetary_norm', 'segment_name', 'cluster_id', 'customer_id')
                ->get()
            : collect();
 
        return view('modules.rfm.index', compact(
            'latestBatch', 'scores', 'segmentStats', 'batchList', 'scatterData'
        ));
    }
 
    /**
     * Halaman hitung ulang: form setting K dan rentang tanggal
     */
    public function create()
    {
        $lastBatch = RfmCalculationBatch::where('status', 'completed')->latest()->first();
        return view('modules.rfm.calculate', compact('lastBatch'));
    }
 
    /**
     * Jalankan kalkulasi RFM + K-Means
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'k_clusters' => 'required|integer|min:2|max:10',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);
 
        $result = $this->rfmService->calculate(
            userId   : Auth::user()->id,
            kClusters: $validated['k_clusters'],
            from     : $validated['date_from'] ? Carbon::parse($validated['date_from']) : null,
            to       : $validated['date_to']   ? Carbon::parse($validated['date_to'])   : null,
        );
 
        return redirect()
            ->route('rfm.batch.show', $result['batch']->id)
            ->with('calculation_steps', $result['steps'])
            ->with('success', $result['success']);
    }
 
    /**
     * Detail satu batch: tampilkan step-by-step perhitungan
     */
    public function showBatch(RfmCalculationBatch $batch)
    {
        $batch->load('triggeredBy');
        $steps = session('calculation_steps', []);
 
        // Jika tidak ada session (akses langsung), rebuild summary dari batch
        if (empty($steps)) {
            $steps = $this->rebuildBatchSummary($batch);
        }
 
        $scores = RfmScore::with('customer')
            ->where('calculation_batch_id', $batch->id)
            ->orderByDesc('rfm_score')
            ->get();
 
        $centroids     = $batch->final_centroids ?? [];
        $clusterLabels = $batch->cluster_labels  ?? [];
 
        return view('modules.rfm.batch-detail', compact('batch', 'steps', 'scores', 'centroids', 'clusterLabels'));
    }
 
    /**
     * Update label cluster (admin edit manual)
     */
    public function updateClusterLabels(Request $request, RfmCalculationBatch $batch)
    {
        $validated = $request->validate([
            'labels'   => 'required|array',
            'labels.*' => 'required|string|max:50',
        ]);
 
        $batch->update(['cluster_labels' => $validated['labels']]);
 
        // Update segment_name di rfm_scores dan rfm_segment_history
        foreach ($validated['labels'] as $clusterId => $label) {
            RfmScore::where('calculation_batch_id', $batch->id)
                ->where('cluster_id', (int) $clusterId)
                ->update(['segment_name' => $label]);
        }
 
        return back()->with('toast', 'Label segmen berhasil diperbarui.');
    }
 
    /**
     * Halaman histori segmen satu pelanggan
     */
    public function customerHistory(int $customerId)
    {
        $history = RfmSegmentHistory::with(['calculationBatch.triggeredBy', 'rfmScore'])
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate(15);
 
        $customer = Customers::findOrFail($customerId);
 
        return view('modules.rfm.customer-history', compact('customer', 'history'));
    }
 
    /**
     * API: data scatter plot untuk chart.js (JSON)
     */
    public function scatterData(RfmCalculationBatch $batch)
    {
        $data = RfmScore::where('calculation_batch_id', $batch->id)
            ->select('recency_norm', 'frequency_norm', 'monetary_norm',
                     'segment_name', 'cluster_id', 'rfm_score', 'customer_id',
                     'recency_days', 'frequency', 'monetary')
            ->with('customer:id,name')
            ->get();
 
        return response()->json($data);
    }
 
    /**
     * API: elbow method — hitung SSE untuk K = 2..max_k
     */
    public function elbowData(Request $request)
    {
        $maxK = min((int) $request->get('max_k', 8), 10);
        $elbowData = [];
 
        // Ambil data normalized dari batch terbaru
        $latestBatch = RfmCalculationBatch::where('status', 'completed')->latest()->first();
        if (!$latestBatch) {
            return response()->json([]);
        }
 
        $scores = RfmScore::where('calculation_batch_id', $latestBatch->id)
            ->select('recency_norm', 'frequency_norm', 'monetary_norm')
            ->get()
            ->toArray();
 
        for ($k = 2; $k <= $maxK; $k++) {
            $sse = $this->rfmService->calculateSseForK($scores, $k);
            $elbowData[] = ['k' => $k, 'sse' => round($sse, 4)];
        }
 
        return response()->json($elbowData);
    }
 
    // -------------------------------------------------------------------------
    private function rebuildBatchSummary(RfmCalculationBatch $batch): array
    {
        $scores = RfmScore::where('calculation_batch_id', $batch->id)->get();
        return [
            [
                'step'        => 1, 'title' => 'Pengambilan data transaksi',
                'stats'       => ['count' => $batch->total_customers],
                'description' => "Batch ini menghitung {$batch->total_customers} pelanggan.",
            ],
            [
                'step'        => 4, 'title' => "K-Means (K={$batch->k_clusters})",
                'iterations'  => $batch->actual_iterations,
                'inertia'     => $batch->inertia,
                'description' => "Konvergen setelah {$batch->actual_iterations} iterasi. SSE = {$batch->inertia}.",
            ],
            [
                'step'        => 6, 'title' => 'Tersimpan ke database',
                'stats'       => ['saved' => $scores->count()],
                'description' => "Total {$scores->count()} skor RFM tersimpan.",
            ],
        ];
    }
}
