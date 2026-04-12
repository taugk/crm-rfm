<?php

namespace App\Http\Controllers\kasir;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\ProductDetail;
use App\Models\Promotions;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KasirController extends Controller
{
    public function index()
{
    $today = Carbon::today();

    // Transaksi hari ini
    $transaksiHariIni = Transaction::whereDate('transaction_date', $today)
        ->where('status', 'completed')
        ->count();

    // Pendapatan hari ini
    $pendapatanHariIni = Transaction::whereDate('transaction_date', $today)
        ->where('status', 'completed')
        ->sum('total_price');

    // Produk terjual
    $produkTerjual = TransactionDetail::whereHas('transaction', function ($q) use ($today) {
        $q->whereDate('transaction_date', $today)
          ->where('status', 'completed');
    })->sum('quantity');

    // Pelanggan hari ini
    $pelangganHariIni = Transaction::whereDate('transaction_date', $today)
        ->distinct('customer_id')
        ->count('customer_id');

    // Top produk
    $topProduk = DB::table('transactions_details')
        ->join('product_details', 'transactions_details.product_detail_id', '=', 'product_details.id')
        ->join('products', 'product_details.product_id', '=', 'products.id')
        ->select('products.name', DB::raw('SUM(transactions_details.quantity) as total'))
        ->groupBy('products.name')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    // Recent transaksi
    $recentTransaksi = Transaction::with('customer')
        ->latest()
        ->limit(5)
        ->get();

    // Chart (7 hari)
    $labels = [];
    $dataTransaksi = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = Carbon::today()->subDays($i);

        $labels[] = $date->format('d M');

        $dataTransaksi[] = Transaction::whereDate('transaction_date', $date)
            ->where('status', 'completed')
            ->count();
    }

    return view('pages.kasir.index', compact(
        'transaksiHariIni',
        'pendapatanHariIni',
        'produkTerjual',
        'pelangganHariIni',
        'topProduk',
        'recentTransaksi',
        'labels',
        'dataTransaksi'
    ));
}


    public function createTransactions()
    {
        $customers = Customers::orderBy('name', 'asc')->get();
        // Mengelompokkan produk untuk tampilan varian di POS
        $products = ProductDetail::with('product')->get();
        
        return view('pages.kasir.transactions.create', compact('customers', 'products'));
    }

        public function transactionStore(Request $request)
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


}
