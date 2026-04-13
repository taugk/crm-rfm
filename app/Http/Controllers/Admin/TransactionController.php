<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TransactionExport;
use App\Http\Controllers\Controller;
use App\Imports\TransactionSheetImport;
use App\Imports\TransactionsImport;
use App\Models\Customers;
use App\Models\ProductDetail;
use App\Models\Promotions;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    /**
     * Menampilkan daftar transaksi dengan filter.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'promotion']);

        // Filter Pencarian (Invoice atau Nama Customer)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter Tanggal (WIB)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('transaction_date', [$start, $end]);
        }

        $data = $query->latest('transaction_date')->paginate(10)->withQueryString();

        return view('pages.admin.transactions.index', compact('data'));
    }

    /**
     * Menampilkan halaman Kasir (POS).
     */
    public function create()
    {
        $customers = Customers::orderBy('name', 'asc')->get();
        // Mengelompokkan produk untuk tampilan varian di POS
        $products = ProductDetail::with('product')->get();
        
        return view('pages.admin.transactions.create', compact('customers', 'products'));
    }

    /**
 * API: Cek Validitas Kode Promo (Digunakan via AJAX di Kasir).
 * Memvalidasi Kode, Status Aktif, Masa Berlaku, Kuota, Minimal Belanja,
 * dan Target Segmen berdasarkan data RFM terbaru.
 */
public function checkPromo(Request $request)
{
    $code = $request->query('code');
    $customerId = $request->query('customer_id');
    $subtotal = (float) $request->query('subtotal', 0);
    $now = now(); // Mengikuti timezone Asia/Jakarta di config/app.php

    // 1. Cari Promo yang Aktif & Dalam Masa Berlaku
    $promo = Promotions::where('promo_code', $code)
        ->where('is_active', true)
        ->where('start_date', '<=', $now)
        ->where('end_date', '>=', $now)
        ->first();

    // Validasi: Apakah promo ada?
    if (!$promo) {
        return response()->json([
            'status' => 'error', 
            'message' => 'Kode promo tidak ditemukan atau sudah kedaluwarsa.'
        ]);
    }

    // 2. Validasi: Kuota Pemakaian
    if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
        return response()->json([
            'status' => 'error', 
            'message' => 'Maaf, kuota promo ini sudah habis.'
        ]);
    }

    // 3. Validasi: Minimal Belanja
    if ($subtotal < (float) $promo->min_spend) {
        return response()->json([
            'status' => 'error', 
            'message' => 'Minimal belanja untuk promo ini adalah Rp ' . number_format($promo->min_spend, 0, ',', '.')
        ]);
    }

    // 4. Validasi: Target Segmen (RFM Check)
    // Jika target_segment diisi (bukan null) dan bukan 'all'
    if ($promo->target_segment && $promo->target_segment !== 'all') {
        
        // Ambil segmen terbaru pelanggan dari tabel rfm_scores
        $currentSegment = DB::table('rfm_scores')
            ->where('customer_id', $customerId)
            ->latest('created_at')
            ->value('segment_name');

        // Jika segmen tidak cocok atau pelanggan belum punya skor RFM
        if (!$currentSegment || $currentSegment !== $promo->target_segment) {
            $userSegment = $currentSegment ?? 'Uncategorized (Member Baru)';
            return response()->json([
                'status' => 'error', 
                'message' => "Promo ini khusus untuk segmen '{$promo->target_segment}'. Segmen pelanggan saat ini: {$userSegment}."
            ]);
        }
    }

    // 5. Jika lolos semua validasi, kirim data promo ke front-end
    return response()->json([
        'status' => 'success',
        'message' => 'Kode promo berhasil dipasang!',
        'data' => [
            'id' => $promo->id,
            'promo_name' => $promo->promo_name,
            'discount_type' => $promo->discount_type,
            'discount_value' => (float) $promo->discount_value,
            'min_spend' => (float) $promo->min_spend,
            'target_segment' => $promo->target_segment,
        ]
    ]);
}

    /**
     * Menyimpan transaksi baru.
     */
    /**
 * Menyimpan transaksi baru dengan integrasi Promo dan Loyalty Point.
 */
public function store(Request $request)
{
    // 1. Validasi Input Dasar
    $request->validate([
        'invoice_number' => 'required|unique:transactions,invoice_number',
        'payment_method' => 'required',
        'items'          => 'required|array|min:1',
        'subtotal'       => 'required|numeric',
        'total_price'    => 'required|numeric',
    ]);

    DB::beginTransaction();

    try {
        // 2. Handling Customer (Walk-in vs Member)
        $customerId = $request->customer_id;
        if ($customerId == 1 && $request->filled('walkin_name')) {
            $newCustomer = Customers::create([
                'name'   => $request->walkin_name,
                'type'   => 'walk in',
                'status' => 'active',
                'role'   => 'customer',
            ]);
            $customerId = $newCustomer->id;
        }

        // 3. Simpan Header Transaksi
        $transaction = Transaction::create([
            'invoice_number'   => $request->invoice_number,
            'customer_id'      => $customerId,
            'promotion_id'     => $request->promotion_id,
            'discount_amount'  => $request->discount_amount ?? 0,
            'subtotal'         => $request->subtotal,
            'tax_total'        => $request->tax_total ?? 0,
            'total_price'      => $request->total_price,
            'status'           => 'completed', 
            'payment_method'   => $request->payment_method,
            'notes'            => $request->notes,
            'transaction_date' => now(),
        ]);

        // 4. Simpan Detail Transaksi & Potong Stok
        foreach ($request->items as $item) {
            TransactionDetail::create([
                'transaction_id'    => $transaction->id,
                'product_detail_id' => $item['product_detail_id'],
                'quantity'          => $item['qty'],
                'price_at_purchase' => $item['price'],
                'subtotal'          => $item['qty'] * $item['price'],
            ]);

            // Potong Stok
            ProductDetail::where('id', $item['product_detail_id'])->decrement('stock', $item['qty']);
        }

        // 5. Integrasi Promo: Validasi Segment & Update Kuota
        if ($request->filled('promotion_id')) {
            $this->applyPromotionInternal($request->promotion_id, $customerId);
        }

        // 6. Integrasi Loyalty: Proses Poin (Hanya Member)
        $this->processLoyaltyPoints($transaction, $customerId);

        DB::commit();
        
        Log::info("Transaction Success: {$transaction->invoice_number}");
        return back()->with('success', 'Transaksi #' . $request->invoice_number . ' Berhasil!');

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Transaction Failed', [
            'invoice' => $request->invoice_number,
            'error'   => $e->getMessage()
        ]);

        return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
    }
}
    /**
     * Menampilkan detail transaksi.
     */
    public function show($id)
    {
        $transaction = Transaction::with([
            'details.product_detail.product', 
            'customer', 
            'promotion'
        ])->findOrFail($id);

        return view('pages.admin.transactions.show', compact('transaction'));
    }

    /**
     * Export ke Excel.
     */
    public function export(Request $request) 
    {
        $filters = $request->only(['search', 'status', 'start_date', 'end_date']);
        $fileName = 'Laporan_Riwayat_Transaksi_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new TransactionExport($filters), $fileName);
    }

    /**
     * Import dari Excel.
     */
     public function importProcess(Request $request)
    {
        // ── Validasi file ───────────────────────────────────────────
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            // ── Jalankan import ─────────────────────────────────────
            $import = new TransactionSheetImport();
            $import->import($request->file('file'));
            // atau: Excel::import($import, $request->file('file'));

            // ── Ambil hasil ─────────────────────────────────────────
            $imported  = $import->getImportedCount();
            $updated   = $import->getUpdatedCount();
            $failures  = $import->getFormattedFailures();

            // ── Redirect ke halaman index ───────────────────────────
            return redirect()
                ->route('admin.transactions')
                ->with('success', 'Import berhasil')
                ->with('imported', $imported)
                ->with('updated', $updated)
                ->with('failed', count($failures))
                ->with('failures', $failures);

        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
 * Memproses poin loyalty hanya untuk tipe 'member'
 */
/**
 * Validasi Internal Promo: Cek Kuota & Target Segment.
 */
private function applyPromotionInternal($promoId, $customerId)
{
    $promo = Promotions::lockForUpdate()->find($promoId);
    if (!$promo) return;

    // 1. Validasi Target Segment via RFM
    if ($promo->target_segment && $promo->target_segment !== 'all') {
        // Query langsung ke tabel rfm_scores untuk data terbaru
        $currentSegment = DB::table('rfm_scores')
            ->where('customer_id', $customerId)
            ->latest('created_at')
            ->value('segment_name');

        if ($currentSegment !== $promo->target_segment) {
            throw new \Exception("Transaksi ditolak: Segmen pelanggan (" . ($currentSegment ?? 'Uncategorized') . ") tidak sesuai dengan target promo ({$promo->target_segment}).");
        }
    }

    // 2. Validasi Kuota
    if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
        throw new \Exception("Kuota promo habis.");
    }

    $promo->increment('used_count');
}

/**
 * Menghitung dan mencatat poin loyalty.
 * Hanya berlaku jika customer bertipe 'member'.
 */
private function processLoyaltyPoints($transaction, $customerId)
{
    $customer = Customers::find($customerId);

    // Filter: Hanya tipe 'member' yang mendapat poin
    if (!$customer || $customer->type !== 'member') {
        return;
    }

    // Ambil aturan poin aktif berdasarkan nominal belanja terkecil yang terpenuhi
    $rule = \App\Models\LoyaltyRule::where('is_active', true)
                ->where('min_purchase', '<=', $transaction->total_price)
                ->orderBy('min_purchase', 'desc')
                ->first();

    if ($rule) {
        // Hitung kelipatan poin
        $multiplier = floor($transaction->total_price / $rule->min_purchase);
        $pointsToAdd = $multiplier * $rule->points_earned;

        if ($pointsToAdd > 0) {
            // 1. Update total poin di master customer
            $customer->increment('total_points', $pointsToAdd);
            $customer->update(['last_purchase_at' => now()]);

            // 2. Catat riwayat detail ke tabel loyalty_points
            \App\Models\LoyaltyPoints::create([
                'customer_id'    => $customer->id,
                'transaction_id' => $transaction->id,
                'amount'         => $pointsToAdd,
                'description'    => "Poin dari transaksi #" . $transaction->invoice_number,
                'type'           => 'earn'
            ]);

            Log::debug("Points Awarded: {$pointsToAdd} to Customer ID {$customer->id}");
        }
    }
}
}