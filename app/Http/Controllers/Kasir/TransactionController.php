<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customers;
use App\Models\Promotions;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\ProductDetail;
use App\Models\LoyaltyPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Menampilkan halaman POS
     */
    public function index()
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
            $promos = Promotions::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();
        } catch (\Exception $e) {
            $promos = collect([]);
        }
        
        // Debug: cek apakah data ada
        if ($products->isEmpty()) {
            \Log::warning('Tidak ada produk yang ditemukan untuk POS');
        }
        
        return view('pages.kasir.pos', compact('products', 'customers', 'promos'));
    }

    /**
     * Memproses Transaksi (Checkout)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'items' => 'required|array|min:1',
            'payment_method' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'promo_code' => 'nullable|string',
            'use_points' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsToSave = [];
            $customerId = $request->customer_id;

            // 2. Loop Items: Hitung Subtotal & Cek Stok
            foreach ($request->items as $item) {
                // Validasi item memiliki id dan qty
                if (!isset($item['id']) || !isset($item['qty'])) {
                    throw new \Exception("Data item tidak lengkap.");
                }

                // Cari detail produk yang punya stok (FIFO simple)
                $productDetail = ProductDetail::where('product_id', $item['id'])
                    ->where('stock', '>', 0)
                    ->first();

                if (!$productDetail || $productDetail->stock < $item['qty']) {
                    $product = Product::find($item['id']);
                    $productName = $product ? $product->name : 'Produk tidak dikenal';
                    throw new \Exception("Stok produk '{$productName}' tidak mencukupi.");
                }

                $price = $productDetail->product->price;
                $itemSubtotal = $price * $item['qty'];
                $subtotal += $itemSubtotal;

                $itemsToSave[] = [
                    'product_detail_id' => $productDetail->id,
                    'quantity' => $item['qty'],
                    'price_at_purchase' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 3. Logika Diskon Promo
            $discountAmount = 0;
            $promotionId = null;
            if ($request->promo_code) {
                $promo = Promotions::where('promo_code', $request->promo_code)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if ($promo) {
                    $promotionId = $promo->id;
                    if ($promo->discount_type === 'percentage') {
                        $discountAmount = ($subtotal * $promo->discount_value) / 100;
                        // Batasi diskon tidak melebihi subtotal
                        if ($discountAmount > $subtotal) {
                            $discountAmount = $subtotal;
                        }
                    } else {
                        $discountAmount = $promo->discount_value;
                        // Batasi diskon tidak melebihi subtotal
                        if ($discountAmount > $subtotal) {
                            $discountAmount = $subtotal;
                        }
                    }
                    $promo->increment('used_count');
                }
            }

            // 4. Logika Pemotongan Poin (Redeem)
            $pointsValue = 0;
            $pointsUsed = 0;
            
            if ($request->use_points && $customerId) {
                $customer = Customers::find($customerId);
                if ($customer && $customer->total_points > 0 && $customer->type === 'member') {
                    // Konversi poin ke nilai rupiah (contoh: 100 poin = Rp 1.000)
                    $pointsValue = $customer->total_points * 10; // Sesuaikan dengan kebijakan Anda
                    $pointsUsed = $customer->total_points;
                    
                    // Pastikan nilai poin tidak melebihi total yang harus dibayar setelah diskon
                    $tempTotal = $subtotal - $discountAmount;
                    if ($pointsValue > $tempTotal) {
                        $pointsValue = $tempTotal;
                        // Hitung ulang poin yang digunakan berdasarkan nilai yang dipotong
                        $pointsUsed = floor($pointsValue / 10);
                    }
                    
                    if ($pointsUsed > 0) {
                        // Catat History Poin Keluar
                        LoyaltyPoints::create([
                            'customer_id' => $customerId,
                            'transaction_id' => null,
                            'amount' => -$pointsUsed, // Nilai negatif untuk pengurangan poin
                            'description' => "Redeem poin pada transaksi POS",
                            'type' => 'redeem'
                        ]);

                        $customer->total_points -= $pointsUsed;
                        $customer->save();
                    }
                }
            }

            // 5. Kalkulasi Final
            $tax = $subtotal * 0.11; // PPN 11%
            $totalAfterDiscount = $subtotal - $discountAmount;
            $totalPrice = $totalAfterDiscount + $tax - $pointsValue;
            
            // Pastikan total tidak negatif
            if ($totalPrice < 0) {
                $totalPrice = 0;
            }

            // 6. Simpan Header Transaksi
            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'customer_id' => $customerId ?? 1, // Pastikan ID 1 adalah "Guest"
                'promotion_id' => $promotionId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount + $pointsValue,
                'tax_total' => $tax,
                'total_price' => $totalPrice,
                'status' => 'completed',
                'payment_method' => $request->payment_method,
                'transaction_date' => now(),
            ]);

            // 7. Simpan Detail & Update Stok Produk
            foreach ($itemsToSave as $detail) {
                $detail['transaction_id'] = $transaction->id;
                TransactionDetail::create($detail);

                // Potong Stok
                ProductDetail::find($detail['product_detail_id'])->decrement('stock', $detail['quantity']);
            }

            // 8. Berikan Poin Baru (Earn) jika customer adalah Member
            if ($customerId) {
                $customer = Customers::find($customerId);
                if ($customer && $customer->type === 'member') {
                    // Contoh Rule: Setiap belanja Rp 10.000 dapat 100 poin
                    $newPoints = floor($totalPrice / 10000) * 100;
                    
                    if ($newPoints > 0) {
                        $customer->increment('total_points', $newPoints);
                        $customer->update(['last_purchase_at' => now()]);

                        LoyaltyPoints::create([
                            'customer_id' => $customerId,
                            'transaction_id' => $transaction->id,
                            'amount' => $newPoints,
                            'description' => "Poin masuk dari transaksi " . $transaction->invoice_number,
                            'type' => 'earn'
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Berhasil!',
                'invoice' => $transaction->invoice_number,
                'total_price' => $totalPrice
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error untuk debugging
            \Log::error('Transaction Error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan detail transaksi berdasarkan invoice
     */
    public function show($invoice)
    {
        try {
            $transaction = Transaction::with(['customer', 'promotion', 'details.productDetail.product'])
                ->where('invoice_number', $invoice)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Cetak struk transaksi
     */
    public function print($id)
    {
        try {
            $transaction = Transaction::with(['customer', 'promotion', 'details.productDetail.product'])
                ->findOrFail($id);

            return view('pages.kasir.print-struk', compact('transaction'));
        } catch (\Exception $e) {
            return redirect()->route('kasir.pos')->with('error', 'Transaksi tidak ditemukan');
        }
    }

    /**
     * Mendapatkan informasi customer termasuk poin
     */
    public function getCustomerInfo($id)
    {
        try {
            $customer = Customers::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'type' => $customer->type,
                    'total_points' => $customer->total_points,
                    'points_value' => $customer->total_points * 10 // Sesuaikan dengan konversi poin
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Validasi promo code
     */
    public function validatePromo(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        try {
            $promo = Promotions::where('promo_code', $request->promo_code)
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if (!$promo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode promo tidak valid atau sudah kadaluarsa'
                ]);
            }

            // Hitung diskon
            if ($promo->discount_type === 'percentage') {
                $discountAmount = ($request->subtotal * $promo->discount_value) / 100;
                $discountAmount = min($discountAmount, $request->subtotal); // Tidak melebihi subtotal
            } else {
                $discountAmount = min($promo->discount_value, $request->subtotal);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $promo->id,
                    'code' => $promo->promo_code,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => $promo->discount_value,
                    'discount_amount' => $discountAmount,
                    'description' => $promo->description
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}