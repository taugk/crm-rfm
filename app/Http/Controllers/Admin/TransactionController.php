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
     */
    public function checkPromo(Request $request)
    {
        $code = $request->query('code');
        $now = now(); // Berdasarkan Asia/Jakarta

        $promo = Promotions::where('promo_code', $code)
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if (!$promo) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Kode promo tidak ditemukan atau sudah kedaluwarsa.'
            ]);
        }

        // Cek Kuota Pemakaian
        if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Maaf, kuota promo ini sudah habis.'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $promo->id,
                'promo_name' => $promo->promo_name,
                'discount_type' => $promo->discount_type,
                'discount_value' => (float) $promo->discount_value,
                'min_spend' => (float) $promo->min_spend,
            ]
        ]);
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(Request $request)
{
    // Log 1: Pantau data yang masuk dari view
    Log::info('POS Transaction Start', [
        'invoice' => $request->invoice_number,
        'payload' => $request->all()
    ]);

    $request->validate([
        'invoice_number' => 'required|unique:transactions,invoice_number',
        'payment_method' => 'required',
        'items'          => 'required|array|min:1',
        'subtotal'       => 'required|numeric',
        'total_price'    => 'required|numeric',
    ]);

    DB::beginTransaction();

    try {
        // 1. Handling Customer
        $customerId = $request->customer_id;
        if ($customerId == 1 && $request->filled('walkin_name')) {
            Log::debug('Processing Walk-in Customer', ['name' => $request->walkin_name]);
            $newCustomer = Customers::create([
                'name'   => $request->walkin_name,
                'type'   => 'walk in',
                'status' => 'active',
                'role'   => 'customer',
            ]);
            $customerId = $newCustomer->id;
        }

        // 2. Simpan Header Transaksi
        Log::debug('Creating Transaction Header', [
            'promotion_id' => $request->promotion_id,
            'discount' => $request->discount_amount
        ]);

        $transaction = Transaction::create([
            'invoice_number'   => $request->invoice_number,
            'customer_id'      => $customerId,
            'promotion_id'     => $request->promotion_id,
            'discount_amount'  => $request->discount_amount ?? 0,
            'subtotal'         => $request->subtotal,
            'tax_total'        => $request->tax_total,
            'total_price'      => $request->total_price,
            'status'           => 'completed', 
            'payment_method'   => $request->payment_method,
            'notes'            => $request->customer_id == 1 ? "Walk-in: " . $request->walkin_name : null,
            'transaction_date' => now(),
        ]);

        // 3. Simpan Detail Transaksi & Potong Stok
        foreach ($request->items as $index => $item) {
            Log::debug("Processing Item #{$index}", ['product_detail_id' => $item['product_detail_id']]);
            
            TransactionDetail::create([
                'transaction_id'    => $transaction->id,
                'product_detail_id' => $item['product_detail_id'],
                'quantity'          => $item['qty'],
                'price_at_purchase' => $item['price'],
                'subtotal'          => $item['qty'] * $item['price'],
            ]);

            $product = ProductDetail::find($item['product_detail_id']);
            if ($product) {
                $oldStock = $product->stock;
                $product->decrement('stock', $item['qty']);
                Log::debug("Stock Updated for Product ID: {$product->id}", [
                    'before' => $oldStock, 
                    'after' => $product->fresh()->stock
                ]);
            }
        }

        // 4. Update Kuota Promo
        if ($request->filled('promotion_id')) {
            $promo = Promotions::find($request->promotion_id);
            if ($promo) {
                $promo->increment('used_count');
                Log::info('Promotion Applied & Usage Incremented', [
                    'promo_code' => $promo->promo_code,
                    'new_used_count' => $promo->fresh()->used_count
                ]);
            }
        }

        // 5. Update Poin Member
        $customer = Customers::find($customerId);
        if ($customer && $customer->type === 'member') {
            $points = floor($request->total_price / 1000); 
            $customer->increment('total_points', $points);
            $customer->update(['last_purchase_at' => now()]);
            
            Log::info('Member Points Awarded', [
                'customer' => $customer->name,
                'points_added' => $points,
                'total_points' => $customer->fresh()->total_points
            ]);
        }

        DB::commit();
        Log::info('Transaction Successfully Committed', ['invoice' => $request->invoice_number]);
        
        return back()->with('success', 'Transaksi #' . $request->invoice_number . ' Berhasil!');

    } catch (\Exception $e) {
        DB::rollBack();
        
        // Log Error Fatal
        Log::error('FATAL TRANSACTION FAILURE', [
            'invoice' => $request->invoice_number,
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()->with('error', 'Gagal Simpan Transaksi: ' . $e->getMessage())->withInput();
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
}