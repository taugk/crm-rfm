<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\PointRedemption;
use App\Models\PointReward;
use App\Models\Promotions;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\TransactionDetail;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // TAMBAHKAN INI
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
class CustomersController extends Controller
{
    /**
     * Dashboard Pelanggan: Ringkasan poin & progres goal
     */
    public function index()
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();

        // 1. Eager Loading relasi untuk menghindari N+1 Query
        $customer->load(['rfmScore']);

        // 2. Ambil 5 Transaksi Terakhir
        $transactions = $customer->transactions()
            ->latest()
            ->take(5)
            ->get();

        // 3. Hitung Progres Reward (Next Goal)
        $nextReward = PointReward::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->where('points_required', '>', $customer->total_points)
            ->orderBy('points_required', 'asc')
            ->first();

        // 4. Ambil 3 Reward untuk menu cepat penukaran
        $availableRewards = PointReward::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->orderBy('points_required', 'asc')
            ->take(3)
            ->get();

        // 5. Ambil data Produk (Menu Favorit/Terbaru)
        $featuredProducts = \App\Models\Product::with(['details'])
            ->where('status', 'active')
            ->latest()
            ->take(4)
            ->get();

        return view('pages.customers.index', [
            'customer'         => $customer,
            'transactions'     => $transactions,
            'nextReward'       => $nextReward,
            'availableRewards' => $availableRewards,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    /**
     * Halaman Menu Member
     */
    public function menu(Request $request)
    {
        Log::info('Menu Page Accessed', [
            'timestamp' => now()->toDateTimeString(),
            'customer_id' => Auth::guard('customers')->id(),
            'customer_name' => Auth::guard('customers')->user()->name ?? 'unknown'
        ]);

        try {
            // Ambil semua produk aktif dengan detailnya
            $products = Product::with('details')
                ->where('status', 'active')
                ->get();
            
            // Urutkan produk dengan multiple criteria (produk dengan stok > 0 di atas)
            $products = $products->sort(function($a, $b) {
                $aStock = $a->details ? $a->details->stock : 0;
                $bStock = $b->details ? $b->details->stock : 0;
                $aHasStock = ($aStock > 0);
                $bHasStock = ($bStock > 0);
                
                // Jika A punya stock dan B tidak, A di atas
                if ($aHasStock && !$bHasStock) return -1;
                // Jika B punya stock dan A tidak, B di atas
                if (!$aHasStock && $bHasStock) return 1;
                // Jika kedua produk punya stock, urutkan berdasarkan stock terbanyak
                if ($aHasStock && $bHasStock) return $bStock - $aStock;
                // Jika kedua produk tidak punya stock, urutkan berdasarkan nama A-Z
                return strcmp($a->name, $b->name);
            })->values();
            
            Log::info('Products loaded for Menu', [
                'total_products' => $products->count(),
                'products_with_stock' => $products->filter(fn($p) => $p->details && $p->details->stock > 0)->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching products for Menu: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $products = collect([]);
        }
        
        try {
            // Ambil semua kategori
            $categories = \App\Models\Category::all();
            Log::info('Categories loaded for Menu', [
                'total_categories' => $categories->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories for Menu: ' . $e->getMessage());
            $categories = collect([]);
        }
        
        // Filter berdasarkan kategori jika ada
        if ($request->has('category') && $request->category != 'all') {
            $products = $products->filter(function($product) use ($request) {
                return $product->category && $product->category->name == $request->category;
            });
        }
        
        // Filter berdasarkan pencarian jika ada
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $products = $products->filter(function($product) use ($search) {
                return str_contains(strtolower($product->name), $search);
            });
        }
        
        // Ambil keranjang dari session
        $cart = session()->get('customer_cart', []);
        
        return view('pages.customers.menu', [
            'customer' => Auth::guard('customers')->user(),
            'categories' => $categories,
            'featuredProducts' => $products,
            'cart' => $cart
        ]);
    }
    /**
 * ==================== KERANJANG & CHECKOUT ====================
 */

/**
 * Tambah ke keranjang (AJAX)
 */
public function addToCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1'
    ]);

    // Ambil produk beserta detailnya
    $product = Product::with('details')->findOrFail($request->product_id);
    
    // Ambil detail produk (hasOne -> ambil langsung)
    $productDetail = $product->details;
    
    // Cek apakah produk memiliki detail
    if (!$productDetail) {
        return response()->json([
            'success' => false,
            'message' => 'Produk tidak memiliki data stok'
        ], 400);
    }
    
    // Cek stok
    if ($productDetail->stock < $request->quantity) {
        return response()->json([
            'success' => false,
            'message' => 'Stok produk tidak mencukupi. Stok tersedia: ' . $productDetail->stock
        ], 400);
    }

    $cart = session()->get('customer_cart', []);
    
    if (isset($cart[$product->id])) {
        // Cek apakah stok cukup untuk penambahan
        $newQuantity = $cart[$product->id]['quantity'] + $request->quantity;
        if ($productDetail->stock < $newQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $productDetail->stock
            ], 400);
        }
        $cart[$product->id]['quantity'] = $newQuantity;
    } else {
        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $request->quantity,
            'stock' => $productDetail->stock,
            'image' => $product->image
        ];
    }
    
    session()->put('customer_cart', $cart);
    
    return response()->json([
        'success' => true,
        'message' => 'Produk ditambahkan ke keranjang',
        'cart_count' => count($cart),
        'cart' => $cart
    ]);
}

/**
 * Update keranjang (AJAX)
 */
public function updateCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:0'
    ]);

    $cart = session()->get('customer_cart', []);
    
    if (!isset($cart[$request->product_id])) {
        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan di keranjang'
        ], 404);
    }
    
    if ($request->quantity <= 0) {
        unset($cart[$request->product_id]);
    } else {
        // Validasi stok
        $product = Product::with('details')->find($request->product_id);
        $productDetail = $product->details;
        
        if ($productDetail && $productDetail->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $productDetail->stock
            ], 400);
        }
        
        $cart[$request->product_id]['quantity'] = $request->quantity;
    }
    
    session()->put('customer_cart', $cart);
    
    return response()->json([
        'success' => true,
        'cart_count' => count($cart),
        'cart' => $cart
    ]);
}

/**
 * Checkout - Proses pesanan
 */
public function checkout(Request $request)
{
    $cart = session()->get('customer_cart', []);
    
    if (empty($cart)) {
        return response()->json([
            'success' => false,
            'message' => 'Keranjang belanja kosong'
        ], 400);
    }
    
    $request->validate([
        'payment_method' => 'required|in:cash,qris',
        'use_points' => 'nullable|boolean'
    ]);

    try {
        DB::beginTransaction();
        
        $customer = Auth::guard('customers')->user();
        $subtotal = 0;
        $itemsToSave = [];
        
        // Hitung subtotal dan cek stok
        foreach ($cart as $item) {
            // Ambil detail produk
            $product = Product::with('details')->find($item['id']);
            $productDetail = $product->details;
            
            if (!$productDetail) {
                throw new \Exception("Produk {$item['name']} tidak memiliki data stok");
            }
            
            if ($productDetail->stock < $item['quantity']) {
                throw new \Exception("Stok produk {$item['name']} tidak mencukupi. Tersedia: {$productDetail->stock}");
            }
            
            $subtotal += $item['price'] * $item['quantity'];
            
            $itemsToSave[] = [
                'product_detail_id' => $productDetail->id,
                'quantity' => $item['quantity'],
                'price_at_purchase' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
            ];
        }
        
        // Hitung pajak
        $tax = $subtotal * 0.11;
        $totalPrice = $subtotal + $tax;
        
        // Hitung poin yang akan didapat (setiap Rp 10.000 = 10 poin)
        $pointsEarned = floor($totalPrice / 10000) * 10;
        
        // Simpan transaksi
        $transaction = Transaction::create([
            'invoice_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id' => $customer->id,
            'promotion_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_total' => $tax,
            'total_price' => $totalPrice,
            'status' => 'completed',
            'payment_method' => $request->payment_method,
            'transaction_date' => now(),
        ]);
        
        // Simpan detail transaksi dan update stok
        foreach ($itemsToSave as $detail) {
            $detail['transaction_id'] = $transaction->id;
            TransactionDetail::create($detail);
            
            // Update stok produk
            $productDetail = ProductDetail::find($detail['product_detail_id']);
            $productDetail->decrement('stock', $detail['quantity']);
        }
        
        // Tambahkan poin ke customer
        if ($pointsEarned > 0) {
            $customer->increment('total_points', $pointsEarned);
            
            LoyaltyPoints::create([
                'customer_id' => $customer->id,
                'transaction_id' => $transaction->id,
                'amount' => $pointsEarned,
                'description' => "Poin dari transaksi {$transaction->invoice_number}",
                'type' => 'earn'
            ]);
        }
        
        // Hapus keranjang
        session()->forget('customer_cart');
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => "Pesanan berhasil! Poin +{$pointsEarned}",
            'redirect' => route('customers.show.transactions', $transaction->id)
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
    /**
     * Halaman Reward: Daftar item yang bisa ditukar
     */
    public function rewards(Request $request)
    {
        $customer = Auth::guard('customers')->user();
        
        $query = PointReward::where('is_active', true);

        if ($request->has('type') && $request->type != 'all') {
            $query->where('reward_type', $request->type);
        }

        $rewards = $query->orderBy('points_required', 'asc')->get();

        return view('pages.customers.redeem', compact('customer', 'rewards'));
    }

    /**
     * Proses penukaran poin (Member request - status pending)
     */
    public function redeem(Request $request)
    {
        /** @var \App\Models\Customers $customer */
        $request->validate(['reward_id' => 'required|exists:point_rewards,id']);
        
        $reward = PointReward::findOrFail($request->reward_id);
        $customer = Auth::guard('customers')->user();

        // 1. Cek kecukupan poin
        if ($customer->total_points < $reward->points_required) {
            return back()->with('error', 'Poin Anda tidak mencukupi.');
        }

        // 2. Cek stok
        $isNonPhysical = in_array($reward->reward_type, ['voucher', 'other']);
        if (!$isNonPhysical && !is_null($reward->stock) && $reward->stock <= 0) {
            return back()->with('error', 'Maaf, stok hadiah ini sedang habis.');
        }

        try {
            DB::transaction(function () use ($customer, $reward, $isNonPhysical) {
                // 1. Buat Record Penukaran dengan status PENDING
                $redemption = PointRedemption::create([
                    'redemption_code' => $this->generateRedemptionCode(),
                    'customer_id'     => $customer->id,
                    'point_reward_id' => $reward->id,
                    'points_used'     => $reward->points_required,
                    'status'          => 'pending',
                    'admin_notes'     => "Request penukaran oleh member: " . $customer->name
                ]);

                // 2. Catat di Mutasi Poin (pengurangan sementara? Tidak, karena masih pending)
                // Untuk pending, poin belum dikurangi. Kita kurangi saat admin konfirmasi.
                // Tapi karena di sistem lama Anda langsung mengurangi, saya sesuaikan:
                // OPSI: Kurangi poin sekarang juga (seperti kode lama Anda)
                $customer->decrement('total_points', $reward->points_required);

                // Catat di loyalty_points
                LoyaltyPoints::create([
                    'customer_id' => $customer->id,
                    'amount'      => -$reward->points_required, // Negatif untuk redeem
                    'description' => 'Tukar Reward: ' . $reward->name . ' (Menunggu Konfirmasi)',
                    'type'        => 'redeem'
                ]);

                // 3. Kurangi Stok HANYA jika bukan non-fisik
                if (!$isNonPhysical && !is_null($reward->stock)) {
                    $reward->decrement('stock');
                }
            });

            return back()->with('success', 'Request penukaran berhasil! Silakan tunggu konfirmasi dari kasir.');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * ==================== RIWAYAT PENUKARAN POIN ====================
     * Menampilkan riwayat penukaran poin member
     */
    public function pointsHistory()
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();
        
        $redemptions = PointRedemption::with('reward')
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $totalPointsUsed = PointRedemption::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->sum('points_used');
        
        $totalRedemptions = PointRedemption::where('customer_id', $customer->id)->count();
        $currentPoints = $customer->total_points ?? 0;
        
        return view('pages.customers.points-history', compact(
            'redemptions',
            'totalPointsUsed',
            'totalRedemptions',
            'currentPoints'
        ));
    }

    /**
     * Detail penukaran poin (AJAX)
     */
    public function pointsDetail($id)
    {
        /** @var \App\Models\Customers $customer */
        $redemption = PointRedemption::with('reward')
            ->where('customer_id', Auth::guard('customers')->id())
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $redemption
        ]);
    }

    /**
     * Print voucher penukaran
     */
    public function printVoucher($id)
    {
        /** @var \App\Models\Customers $customer */
        $redemption = PointRedemption::with('reward')
            ->where('customer_id', Auth::guard('customers')->id())
            ->findOrFail($id);
        
        if ($redemption->status !== 'completed') {
            return redirect()->back()->with('error', 'Penukaran belum selesai dikonfirmasi');
        }
        
        if ($redemption->reward->reward_type !== 'voucher') {
            return redirect()->back()->with('error', 'Hanya reward tipe voucher yang bisa dicetak');
        }
        
        return view('pages.customers.print-voucher', compact('redemption'));
    }

    /**
     * Generate unique redemption code
     */
    private function generateRedemptionCode()
    {
        do {
            $code = 'RDM-' . strtoupper(Str::random(10)) . '-' . rand(1000, 9999);
        } while (PointRedemption::where('redemption_code', $code)->exists());
        
        return $code;
    }

    /**
     * Promo: Berdasarkan segmentasi RFM
     */
    public function promotions()
    {
        $customer = Auth::guard('customers')->user();
        
        $userSegment = optional($customer->rfmScore)->segment_name ?? 'New Member';

        $promos = Promotions::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function ($query) use ($userSegment) {
                $query->whereNull('target_segment')
                    ->orWhere('target_segment', '')
                    ->orWhere('target_segment', 'all')
                    ->orWhere('target_segment', $userSegment);
            })
            ->whereRaw('used_count < usage_limit')
            ->latest()
            ->get();

        return view('pages.customers.promo', compact('customer', 'promos', 'userSegment'));
    }

    public function transactions(Request $request)
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();

        $query = Transaction::with(['details.product_detail.product'])
            ->where('customer_id', $customer->id);

        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('period')) {
            $days = (int) $request->period;
            $query->where('transaction_date', '>=', now()->subDays($days));
        }

        $transactions = $query->latest('transaction_date')->paginate(10);

        return view('pages.customers.transactions', compact('transactions'));
    }

    public function transactionShow($id)
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();

        $transaction = Transaction::with([
            'details.product_detail.product', 
            'promotion'
        ])
        ->where('customer_id', $customer->id)
        ->findOrFail($id);

        return view('pages.customers.show-transactions', compact('transaction'));
    }

    public function profile()
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();
        $customer->load(['rfmScore']);

        return view('pages.customers.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();

        $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|unique:customers,phone,'.$customer->id,
            'gender'         => 'nullable|in:male,female,other',
            'date_of_birth'  => 'nullable|date',
            'full_address'   => 'nullable|string',
            'profile_photo'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'phone', 'gender', 'date_of_birth', 'full_address']);

        if ($request->hasFile('profile_photo')) {
            if ($customer->profile_photo) {
                $oldPath = str_replace('storage/', '', $customer->profile_photo);
                Storage::disk('public')->delete($oldPath);
            }
            $data['profile_photo'] = $this->uploadImage($request->file('profile_photo'), $request->name);
        }

        $customer->update($data);

        return back()->with('success', 'Profil Anda telah berhasil diperbarui.');
    }

    /**
     * Helper Upload Image
     */
    private function uploadImage($image, $name)
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
        $filename = time() . '_' . $cleanName . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('customers', $filename, 'public');
        return 'storage/' . $path;
    }

    /**
     * Update Kata Sandi
     */
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\Customers $customer */
        $customer = Auth::guard('customers')->user();

        $request->validate([
            'current_password' => ['required', 'current_password:customers'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed'                => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min'                      => 'Kata sandi baru minimal 8 karakter dengan kombinasi huruf dan angka.',
        ]);

        $customer->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui. Akun Anda kini lebih aman.');
    }
}