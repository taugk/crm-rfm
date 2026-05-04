<?php
// app/Http/Controllers/Kasir/KasirController.php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Customers;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Promotions;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use Carbon\Carbon;

class KasirController extends Controller
{
    /**
     * Dashboard Kasir
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Transaksi hari ini - gunakan value() atau pastikan tidak null
        $transaksiHariIni = Transaction::whereDate('transaction_date', $today)
            ->where('status', 'completed')
            ->count();
        
        // Pendapatan hari ini - gunakan value() atau jika null jadi 0
        $pendapatanHariIni = Transaction::whereDate('transaction_date', $today)
            ->where('status', 'completed')
            ->sum('total_price');
        
        // Jika null, set ke 0
        if ($pendapatanHariIni === null) {
            $pendapatanHariIni = 0;
        }
        
        // Produk terjual hari ini
        $produkTerjual = TransactionDetail::whereHas('transaction', function($query) use ($today) {
            $query->whereDate('transaction_date', $today)
                  ->where('status', 'completed');
        })->sum('quantity');
        
        if ($produkTerjual === null) {
            $produkTerjual = 0;
        }
        
        // Pelanggan hari ini
        $pelangganHariIni = Transaction::whereDate('transaction_date', $today)
            ->where('status', 'completed')
            ->distinct('customer_id')
            ->count('customer_id');
        
        // Top 5 produk terlaris bulan ini
        try {
            $topProduk = ProductDetail::select('products.id', 'products.name', 
                DB::raw('COALESCE(SUM(transactions_details.quantity), 0) as total'))
                ->leftJoin('transactions_details', 'product_details.id', '=', 'transactions_details.product_detail_id')
                ->leftJoin('transactions', 'transactions_details.transaction_id', '=', 'transactions.id')
                ->leftJoin('products', 'product_details.product_id', '=', 'products.id')
                ->where('transactions.status', 'completed')
                ->whereMonth('transactions.transaction_date', Carbon::now()->month)
                ->groupBy('products.id', 'products.name')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $topProduk = collect([]);
        }
        
        // Recent transactions
        try {
            $recentTransaksi = Transaction::with('customer')
                ->where('status', 'completed')
                ->orderBy('transaction_date', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $recentTransaksi = collect([]);
        }
        
        // Chart data (7 hari terakhir)
        $labels = [];
        $dataTransaksi = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $count = Transaction::whereDate('transaction_date', $date)
                ->where('status', 'completed')
                ->count();
            $dataTransaksi[] = $count;
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
    
    /**
     * Halaman POS (Point of Sale)
     */
    public function pos()
    {
        try {
            $products = Product::with(['details' => function($query) {
                $query->where('stock', '>', 0);
            }])->where('status', 'active')->get();
        } catch (\Exception $e) {
            $products = collect([]);
        }
        
        try {
            $customers = Customers::where('status', 'active')->get();
        } catch (\Exception $e) {
            $customers = collect([]);
        }
        
        try {
            $promotions = Promotions::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();
        } catch (\Exception $e) {
            $promotions = collect([]);
        }
        
        // Debug: cek apakah data ada
        if ($products->isEmpty()) {
            \Log::warning('Tidak ada produk yang ditemukan untuk POS');
        }
        
        return view('pages.kasir.pos', compact('products', 'customers', 'promotions'));
    }
    
    /**
     * Store transaksi dari POS
     */
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_detail_id' => 'required|exists:product_details,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'promotion_code' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $items = [];
            
            foreach ($request->items as $item) {
                $productDetail = ProductDetail::with('product')->find($item['product_detail_id']);
                
                if (!$productDetail) {
                    throw new \Exception('Produk tidak ditemukan');
                }
                
                if ($productDetail->stock < $item['quantity']) {
                    throw new \Exception("Stok {$productDetail->product->name} tidak mencukupi");
                }
                
                $price = $productDetail->product->price;
                $itemSubtotal = $price * $item['quantity'];
                $subtotal += $itemSubtotal;
                
                $items[] = [
                    'product_detail' => $productDetail,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $itemSubtotal
                ];
            }
            
            $discountAmount = 0;
            $promotion = null;
            
            if ($request->promotion_code) {
                $promotion = Promotions::where('promo_code', $request->promotion_code)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();
                
                if ($promotion) {
                    if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
                        throw new \Exception('Promo sudah mencapai batas penggunaan');
                    }
                    
                    if ($subtotal >= $promotion->min_spend) {
                        if ($promotion->discount_type == 'percentage') {
                            $discountAmount = $subtotal * ($promotion->discount_value / 100);
                        } else {
                            $discountAmount = $promotion->discount_value;
                        }
                        
                        $promotion->increment('used_count');
                    }
                }
            }
            
            $taxTotal = $subtotal * 0.11;
            $totalPrice = $subtotal - $discountAmount + $taxTotal;
            
            $invoiceNumber = 'INV/' . date('Ymd') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $request->customer_id,
                'promotion_id' => $promotion ? $promotion->id : null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_total' => $taxTotal,
                'total_price' => $totalPrice,
                'status' => 'completed',
                'transaction_date' => now(),
                'payment_method' => $request->payment_method,
                'notes' => $request->notes
            ]);
            
            foreach ($items as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_detail_id' => $item['product_detail']->id,
                    'quantity' => $item['quantity'],
                    'price_at_purchase' => $item['price'],
                    'subtotal' => $item['subtotal']
                ]);
                
                $item['product_detail']->decrement('stock', $item['quantity']);
            }
            
            if ($request->customer_id) {
                $customer = Customers::find($request->customer_id);
                if ($customer && $customer->type == 'member') {
                    $loyaltyRule = LoyaltyRule::where('is_active', true)
                        ->where('min_purchase', '<=', $totalPrice)
                        ->orderBy('min_purchase', 'desc')
                        ->first();
                    
                    if ($loyaltyRule) {
                        $pointsEarned = $loyaltyRule->points_earned;
                        
                        LoyaltyPoint::create([
                            'customer_id' => $customer->id,
                            'transaction_id' => $transaction->id,
                            'amount' => $pointsEarned,
                            'description' => "Poin dari transaksi {$invoiceNumber}",
                            'type' => 'earn'
                        ]);
                        
                        $customer->increment('total_points', $pointsEarned);
                        $customer->update(['last_purchase_at' => now()]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'transaction' => $transaction,
                'invoice_number' => $invoiceNumber
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Halaman riwayat transaksi
     */
    public function transactionHistory(Request $request)
    {
        try {
            $query = Transaction::with('customer')
                ->where('status', 'completed')
                ->orderBy('transaction_date', 'desc');
            
            if ($request->start_date) {
                $query->whereDate('transaction_date', '>=', $request->start_date);
            }
            
            if ($request->end_date) {
                $query->whereDate('transaction_date', '<=', $request->end_date);
            }
            
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }
            
            $transactions = $query->paginate(15);
            $customers = Customers::where('status', 'active')->get();
        } catch (\Exception $e) {
            $transactions = collect([]);
            $customers = collect([]);
        }
        
        return view('pages.kasir.transactions.history', compact('transactions', 'customers'));
    }
    
    /**
     * Detail transaksi
     */
    public function showTransaction($id)
    {
        try {
            $transaction = Transaction::with(['customer', 'details.productDetail.product', 'promotion'])
                ->findOrFail($id);
        } catch (\Exception $e) {
            abort(404, 'Transaksi tidak ditemukan');
        }
        
        return view('pages.kasir.transactions.show', compact('transaction'));
    }
    
    /**
     * Cetak invoice
     */
    public function printInvoice($id)
    {
        try {
            $transaction = Transaction::with(['customer', 'details.productDetail.product', 'promotion'])
                ->findOrFail($id);
        } catch (\Exception $e) {
            abort(404, 'Transaksi tidak ditemukan');
        }
        
        return view('pages.kasir.transactions.invoice', compact('transaction'));
    }
    
    /**
     * Check promo
     */
    public function checkPromo(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string',
            'subtotal' => 'required|numeric'
        ]);
        
        try {
            $promo = Promotions::where('promo_code', $request->promo_code)
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Gagal memeriksa promo'
            ]);
        }
        
        if (!$promo) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluarsa'
            ]);
        }
        
        if ($promo->usage_limit && $promo->used_count >= $promo->usage_limit) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode promo sudah mencapai batas penggunaan'
            ]);
        }
        
        if ($request->subtotal < $promo->min_spend) {
            return response()->json([
                'valid' => false,
                'message' => "Minimal belanja Rp " . number_format($promo->min_spend, 0, ',', '.')
            ]);
        }
        
        $discount = 0;
        if ($promo->discount_type == 'percentage') {
            $discount = $request->subtotal * ($promo->discount_value / 100);
        } else {
            $discount = $promo->discount_value;
        }
        
        return response()->json([
            'valid' => true,
            'discount' => $discount,
            'promotion_id' => $promo->id,
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
            'message' => 'Kode promo berhasil diterapkan'
        ]);
    }
    
    /**
     * Daftar promo aktif
     */
    public function promotions()
    {
        try {
            $promotions = Promotions::where('is_active', true)
                ->where('end_date', '>=', now())
                ->orderBy('start_date', 'desc')
                ->get();
        } catch (\Exception $e) {
            $promotions = collect([]);
        }
        
        return view('pages.kasir.promotions', compact('promotions'));
    }
}