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
use App\Models\LoyaltyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Menampilkan halaman POS
     */
    public function index()
    {
        Log::info('POS Page Accessed', [
            'timestamp' => now()->toDateTimeString(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'unknown'
        ]);

        try {
            // Ambil semua produk aktif dengan detailnya
            $products = Product::with('details')
                ->where('status', 'active')
                ->get();
            
            // Urutkan produk dengan multiple criteria
            $products = $products->sort(function($a, $b) {
                $aStock = $a->details ? $a->details->stock : 0;
                $bStock = $b->details ? $b->details->stock : 0;
                $aHasStock = ($aStock > 0);
                $bHasStock = ($bStock > 0);
                
                if ($aHasStock && !$bHasStock) return -1;
                if (!$aHasStock && $bHasStock) return 1;
                if ($aHasStock && $bHasStock) return $bStock - $aStock;
                return strcmp($a->name, $b->name);
            })->values();
            
            Log::info('Products loaded for POS', [
                'total_products' => $products->count(),
                'products_with_stock' => $products->filter(fn($p) => $p->details && $p->details->stock > 0)->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching products for POS: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $products = collect([]);
        }
        
        try {
            $customers = Customers::where('status', 'active')->get();
            Log::info('Customers loaded for POS', [
                'total_customers' => $customers->count(),
                'member_count' => $customers->where('type', 'member')->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customers for POS: ' . $e->getMessage());
            $customers = collect([]);
        }
        
        try {
            // Ambil semua promo aktif
            $promos = Promotions::where('is_active', true)->get();
            
            // Format promo untuk JavaScript
            $promosFormatted = $promos->map(function($promo) {
                return [
                    'id' => $promo->id,
                    'promo_code' => $promo->promo_code,
                    'promo_name' => $promo->promo_name,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => (float) $promo->discount_value,
                    'min_spend' => (float) ($promo->min_spend ?? 0),
                    'target_segment' => $promo->target_segment ?? 'all',
                    'start_date' => $promo->start_date ? $promo->start_date->toDateTimeString() : null,
                    'end_date' => $promo->end_date ? $promo->end_date->toDateTimeString() : null,
                ];
            });
            
            Log::info('Promotions loaded for POS', [
                'total_promotions' => $promos->count(),
                'active_promotions' => $promos->where('is_active', true)->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching promotions for POS: ' . $e->getMessage());
            $promosFormatted = collect([]);
        }
        
        return view('pages.kasir.pos', [
            'products' => $products,
            'customers' => $customers,
            'promos' => $promosFormatted
        ]);
    }

    /**
     * Hitung poin berdasarkan Loyalty Rule
     */
    private function calculateLoyaltyPoints($totalPrice)
    {
        Log::info('Calculating loyalty points', [
            'total_price' => $totalPrice,
            'timestamp' => now()->toDateTimeString()
        ]);

        // Ambil aturan poin yang aktif, diurutkan dari minimal purchase terbesar ke terkecil
        $activeRules = LoyaltyRule::where('is_active', true)
            ->orderBy('min_purchase', 'desc')
            ->get();
        
        Log::info('Active loyalty rules found', [
            'total_rules' => $activeRules->count(),
            'rules' => $activeRules->map(function($rule) {
                return [
                    'rule_name' => $rule->rule_name,
                    'min_purchase' => $rule->min_purchase,
                    'points_earned' => $rule->points_earned
                ];
            })
        ]);
        
        $totalPoints = 0;
        $appliedRules = [];
        $remainingPrice = $totalPrice;
        
        foreach ($activeRules as $rule) {
            // Hitung berapa kali kelipatan minimal pembelian
            $multiplier = floor($remainingPrice / $rule->min_purchase);
            
            if ($multiplier > 0) {
                $pointsFromRule = $multiplier * $rule->points_earned;
                $totalPoints += $pointsFromRule;
                
                $appliedRules[] = [
                    'rule_name' => $rule->rule_name,
                    'min_purchase' => $rule->min_purchase,
                    'multiplier' => $multiplier,
                    'points_earned_per_unit' => $rule->points_earned,
                    'total_points' => $pointsFromRule
                ];
                
                Log::info('Rule applied', [
                    'rule_name' => $rule->rule_name,
                    'min_purchase' => $rule->min_purchase,
                    'multiplier' => $multiplier,
                    'points_earned' => $pointsFromRule
                ]);
                
                // Kurangi total price untuk menghindari double counting
                $remainingPrice -= ($multiplier * $rule->min_purchase);
                if ($remainingPrice <= 0) break;
            }
        }
        
        Log::info('Loyalty points calculation result', [
            'original_price' => $totalPrice,
            'remaining_price' => $remainingPrice,
            'total_points_earned' => $totalPoints,
            'applied_rules_count' => count($appliedRules),
            'applied_rules' => $appliedRules
        ]);
        
        return [
            'total_points' => $totalPoints,
            'applied_rules' => $appliedRules
        ];
    }

    /**
     * Memproses Transaksi (Checkout)
     */
    public function store(Request $request)
    {
        Log::info('=== START TRANSACTION PROCESSING ===');
        Log::info('Transaction request received', [
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

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
            Log::info('Database transaction started');

            $subtotal = 0;
            $itemsToSave = [];
            $customerId = $request->customer_id;

            Log::info('Processing items', [
                'total_items_in_cart' => count($request->items),
                'customer_id' => $customerId
            ]);

            // 2. Loop Items: Hitung Subtotal & Cek Stok
            foreach ($request->items as $index => $item) {
                Log::info("Processing item #{$index}", [
                    'item_id' => $item['id'],
                    'quantity' => $item['qty']
                ]);

                if (!isset($item['id']) || !isset($item['qty'])) {
                    throw new \Exception("Data item tidak lengkap pada index {$index}");
                }

                $productDetail = ProductDetail::where('product_id', $item['id'])
                    ->where('stock', '>', 0)
                    ->first();

                if (!$productDetail || $productDetail->stock < $item['qty']) {
                    $product = Product::find($item['id']);
                    $productName = $product ? $product->name : 'Produk tidak dikenal';
                    Log::error('Stock insufficient', [
                        'product_id' => $item['id'],
                        'product_name' => $productName,
                        'requested_qty' => $item['qty'],
                        'available_stock' => $productDetail ? $productDetail->stock : 0
                    ]);
                    throw new \Exception("Stok produk '{$productName}' tidak mencukupi.");
                }

                $price = $productDetail->product->price;
                $itemSubtotal = $price * $item['qty'];
                $subtotal += $itemSubtotal;

                Log::info('Item processed', [
                    'product_id' => $item['id'],
                    'product_name' => $productDetail->product->name,
                    'price' => $price,
                    'quantity' => $item['qty'],
                    'subtotal' => $itemSubtotal
                ]);

                $itemsToSave[] = [
                    'product_detail_id' => $productDetail->id,
                    'quantity' => $item['qty'],
                    'price_at_purchase' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            Log::info('Subtotal calculation completed', [
                'subtotal' => $subtotal,
                'total_items' => count($itemsToSave)
            ]);

            // 3. Logika Diskon Promo
            $discountAmount = 0;
            $promotionId = null;
            if ($request->promo_code) {
                Log::info('Processing promo code', ['promo_code' => $request->promo_code]);
                
                $promo = Promotions::where('promo_code', $request->promo_code)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if ($promo) {
                    $promotionId = $promo->id;
                    if ($promo->discount_type === 'percentage') {
                        $discountAmount = ($subtotal * $promo->discount_value) / 100;
                        if ($discountAmount > $subtotal) {
                            $discountAmount = $subtotal;
                        }
                    } else {
                        $discountAmount = $promo->discount_value;
                        if ($discountAmount > $subtotal) {
                            $discountAmount = $subtotal;
                        }
                    }
                    $promo->increment('used_count');
                    
                    Log::info('Promo applied', [
                        'promo_code' => $promo->promo_code,
                        'promo_name' => $promo->promo_name,
                        'discount_type' => $promo->discount_type,
                        'discount_value' => $promo->discount_value,
                        'discount_amount' => $discountAmount
                    ]);
                } else {
                    Log::warning('Invalid or expired promo code', [
                        'promo_code' => $request->promo_code,
                        'current_time' => now()->toDateTimeString()
                    ]);
                }
            }

            // 4. Logika Pemotongan Poin (Redeem)
            $pointsValue = 0;
            $pointsUsed = 0;
            
            if ($request->use_points && $customerId) {
                Log::info('Processing points redemption', ['customer_id' => $customerId]);
                
                $customer = Customers::find($customerId);
                if ($customer && $customer->total_points > 0 && $customer->type === 'member') {
                    Log::info('Customer found for points redemption', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'total_points' => $customer->total_points,
                        'customer_type' => $customer->type
                    ]);
                    
                    // Konversi poin ke nilai rupiah (1 poin = Rp 10)
                    $maxPointsValue = $customer->total_points * 10;
                    
                    // Hitung total yang harus dibayar setelah diskon
                    $tempTotal = $subtotal - $discountAmount;
                    
                    // Maksimal redeem 50% dari total belanja (optional rule)
                    $maxRedeemValue = $tempTotal * 0.5;
                    $pointsValue = min($maxPointsValue, $maxRedeemValue, $tempTotal);
                    
                    // Hitung poin yang digunakan
                    $pointsUsed = floor($pointsValue / 10);
                    
                    Log::info('Points redemption calculation', [
                        'customer_points' => $customer->total_points,
                        'max_points_value' => $maxPointsValue,
                        'temp_total' => $tempTotal,
                        'max_redeem_value' => $maxRedeemValue,
                        'final_points_value' => $pointsValue,
                        'points_to_use' => $pointsUsed
                    ]);
                    
                    if ($pointsUsed > 0) {
                        // Catat History Poin Keluar
                        $loyaltyPoint = LoyaltyPoints::create([
                            'customer_id' => $customerId,
                            'transaction_id' => null,
                            'amount' => -$pointsUsed,
                            'description' => "Redeem {$pointsUsed} poin (Rp {$pointsValue}) pada transaksi POS",
                            'type' => 'redeem'
                        ]);
                        
                        Log::info('Points redemption recorded', [
                            'loyalty_point_id' => $loyaltyPoint->id,
                            'points_used' => $pointsUsed,
                            'points_value' => $pointsValue
                        ]);

                        $customer->total_points -= $pointsUsed;
                        $customer->save();
                        
                        Log::info('Customer points updated after redemption', [
                            'customer_id' => $customer->id,
                            'old_points' => $customer->total_points + $pointsUsed,
                            'new_points' => $customer->total_points
                        ]);
                    } else {
                        Log::info('No points redeemed - calculated points value too low', [
                            'points_used' => $pointsUsed,
                            'points_value' => $pointsValue
                        ]);
                    }
                } else {
                    Log::warning('Points redemption skipped', [
                        'customer_exists' => isset($customer),
                        'customer_points' => isset($customer) ? $customer->total_points : 0,
                        'customer_type' => isset($customer) ? $customer->type : 'unknown',
                        'use_points_flag' => $request->use_points
                    ]);
                }
            }

            // 5. Kalkulasi Final
            $tax = $subtotal * 0.11; // PPN 11%
            $totalAfterDiscount = $subtotal - $discountAmount;
            $totalPrice = $totalAfterDiscount + $tax - $pointsValue;
            
            if ($totalPrice < 0) {
                $totalPrice = 0;
            }

            Log::info('Final price calculation', [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax' => $tax,
                'points_value' => $pointsValue,
                'total_after_discount' => $totalAfterDiscount,
                'final_total_price' => $totalPrice
            ]);

            // 6. Simpan Header Transaksi
            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'customer_id' => $customerId ?? 1,
                'promotion_id' => $promotionId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount + $pointsValue,
                'tax_total' => $tax,
                'total_price' => $totalPrice,
                'status' => 'completed',
                'payment_method' => $request->payment_method,
                'transaction_date' => now(),
            ]);

            Log::info('Transaction saved', [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'total_price' => $transaction->total_price
            ]);

            // 7. Simpan Detail & Update Stok Produk
            foreach ($itemsToSave as $index => $detail) {
                $detail['transaction_id'] = $transaction->id;
                $savedDetail = TransactionDetail::create($detail);
                
                $productDetail = ProductDetail::find($detail['product_detail_id']);
                $oldStock = $productDetail->stock;
                $productDetail->decrement('stock', $detail['quantity']);
                
                Log::info('Transaction detail saved and stock updated', [
                    'detail_index' => $index,
                    'detail_id' => $savedDetail->id,
                    'product_detail_id' => $detail['product_detail_id'],
                    'quantity' => $detail['quantity'],
                    'old_stock' => $oldStock,
                    'new_stock' => $oldStock - $detail['quantity']
                ]);
            }

            // 8. Berikan Poin Baru (Earn) - MENGGUNAKAN LOYALTY RULE
            $newPoints = 0;
            if ($customerId) {
                Log::info('Checking loyalty points eligibility', ['customer_id' => $customerId]);
                
                $customer = Customers::find($customerId);
                if ($customer && $customer->type === 'member') {
                    Log::info('Customer is member, calculating loyalty points', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'transaction_total' => $totalPrice
                    ]);
                    
                    // Hitung poin berdasarkan Loyalty Rule
                    $loyaltyResult = $this->calculateLoyaltyPoints($totalPrice);
                    $newPoints = $loyaltyResult['total_points'];
                    
                    Log::info('Loyalty points calculation result', [
                        'total_price_for_points' => $totalPrice,
                        'points_calculated' => $newPoints,
                        'applied_rules' => $loyaltyResult['applied_rules']
                    ]);
                    
                    if ($newPoints > 0) {
                        $oldPoints = $customer->total_points;
                        $customer->increment('total_points', $newPoints);
                        $customer->update(['last_purchase_at' => now()]);

                        // Buat deskripsi aturan yang diterapkan
                        $rulesDescription = '';
                        foreach ($loyaltyResult['applied_rules'] as $rule) {
                            $rulesDescription .= "{$rule['rule_name']}: {$rule['multiplier']}x {$rule['points_earned_per_unit']} poin = {$rule['total_points']} poin; ";
                        }
                        
                        $loyaltyPoint = LoyaltyPoints::create([
                            'customer_id' => $customerId,
                            'transaction_id' => $transaction->id,
                            'amount' => $newPoints,
                            'description' => "Poin dari transaksi {$transaction->invoice_number}. " . $rulesDescription,
                            'type' => 'earn'
                        ]);
                        
                        Log::info('✅ LOYALTY POINTS EARNED SUCCESSFULLY', [
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'old_points' => $oldPoints,
                            'new_points_added' => $newPoints,
                            'total_points_now' => $customer->total_points,
                            'loyalty_point_id' => $loyaltyPoint->id,
                            'transaction_id' => $transaction->id,
                            'invoice_number' => $transaction->invoice_number,
                            'rules_applied' => $loyaltyResult['applied_rules']
                        ]);
                    } else {
                        Log::info('No loyalty points earned - total price below minimum threshold', [
                            'customer_id' => $customer->id,
                            'transaction_total' => $totalPrice,
                            'minimum_points_rule' => LoyaltyRule::where('is_active', true)->min('min_purchase') ?? 'no rules'
                        ]);
                    }
                } else {
                    Log::info('Customer is not member, skipping loyalty points', [
                        'customer_id' => $customerId,
                        'customer_type' => isset($customer) ? $customer->type : 'customer not found'
                    ]);
                }
            } else {
                Log::info('No customer selected, skipping loyalty points');
            }

            DB::commit();
            Log::info('=== TRANSACTION COMPLETED SUCCESSFULLY ===', [
                'invoice_number' => $transaction->invoice_number,
                'total_price' => $totalPrice,
                'points_earned' => $newPoints,
                'points_used' => $pointsUsed,
                'customer_id' => $customerId,
                'timestamp' => now()->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi Berhasil!',
                'invoice' => $transaction->invoice_number,
                'total_price' => $totalPrice,
                'points_earned' => $newPoints,
                'points_used' => $pointsUsed,
                'debug_info' => [
                    'points_calculation' => [
                        'total_price_for_points' => $totalPrice,
                        'points_earned' => $newPoints,
                        'points_used' => $pointsUsed
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ TRANSACTION FAILED ❌', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Mendapatkan detail transaksi berdasarkan invoice
     */
    public function show($invoice)
    {
        Log::info('Fetching transaction details', ['invoice_number' => $invoice]);
        
        try {
            $transaction = Transaction::with(['customer', 'promotion', 'details.productDetail.product'])
                ->where('invoice_number', $invoice)
                ->firstOrFail();

            Log::info('Transaction found', [
                'invoice_number' => $invoice,
                'transaction_id' => $transaction->id,
                'total_price' => $transaction->total_price
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction not found', [
                'invoice_number' => $invoice,
                'error' => $e->getMessage()
            ]);
            
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
        Log::info('Printing transaction receipt', ['transaction_id' => $id]);
        
        try {
            $transaction = Transaction::with(['customer', 'promotion', 'details.productDetail.product'])
                ->findOrFail($id);

            return view('pages.kasir.print-struk', compact('transaction'));
        } catch (\Exception $e) {
            Log::error('Error printing receipt', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('kasir.pos')->with('error', 'Transaksi tidak ditemukan');
        }
    }

    /**
     * Mendapatkan informasi customer termasuk poin
     */
    public function getCustomerInfo($id)
    {
        Log::info('Fetching customer info', ['customer_id' => $id]);
        
        try {
            $customer = Customers::findOrFail($id);
            
            // Hitung nilai poin berdasarkan aturan redeem
            $pointsValue = $customer->total_points * 10;
            $maxRedeem = min($pointsValue, 50000);
            
            Log::info('Customer info retrieved', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_type' => $customer->type,
                'total_points' => $customer->total_points,
                'points_value' => $pointsValue,
                'max_redeem' => $maxRedeem
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'type' => $customer->type,
                    'total_points' => $customer->total_points,
                    'points_value' => $pointsValue,
                    'max_redeem' => $maxRedeem
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Customer not found', [
                'customer_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Mendapatkan aturan Loyalty Point
     */
    public function getLoyaltyRules()
    {
        Log::info('Fetching loyalty rules');
        
        try {
            $rules = LoyaltyRule::where('is_active', true)
                ->orderBy('min_purchase', 'asc')
                ->get();
            
            Log::info('Loyalty rules retrieved', [
                'total_rules' => $rules->count(),
                'rules' => $rules->map(fn($r) => [
                    'rule_name' => $r->rule_name,
                    'min_purchase' => $r->min_purchase,
                    'points_earned' => $r->points_earned
                ])
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $rules
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching loyalty rules', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil aturan loyalty'
            ], 500);
        }
    }

    /**
     * Validasi promo code
     */
    public function validatePromo(Request $request)
    {
        Log::info('Validating promo code', ['promo_code' => $request->promo_code]);
        
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
                Log::warning('Invalid or expired promo code validation', [
                    'promo_code' => $request->promo_code,
                    'current_time' => now()->toDateTimeString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Kode promo tidak valid atau sudah kadaluarsa'
                ]);
            }

            if ($promo->discount_type === 'percentage') {
                $discountAmount = ($request->subtotal * $promo->discount_value) / 100;
                $discountAmount = min($discountAmount, $request->subtotal);
            } else {
                $discountAmount = min($promo->discount_value, $request->subtotal);
            }

            Log::info('Promo code validated successfully', [
                'promo_code' => $promo->promo_code,
                'promo_name' => $promo->promo_name,
                'discount_amount' => $discountAmount
            ]);

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
            Log::error('Error validating promo code', [
                'promo_code' => $request->promo_code,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}