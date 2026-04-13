<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\PointRedemption;
use App\Models\PointReward;
use App\Models\Promotions; // Pastikan menggunakan singular sesuai standar Laravel
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;


class CustomersController extends Controller
{
    /**
     * Dashboard Pelanggan: Ringkasan poin & progres goal
     */
    /**
 * Menampilkan Dashboard Utama Member
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
    $nextReward = \App\Models\PointReward::where('is_active', true)
        ->where(function($q) {
            $q->whereNull('stock')->orWhere('stock', '>', 0);
        })
        ->where('points_required', '>', $customer->total_points)
        ->orderBy('points_required', 'asc')
        ->first();

    // 4. Ambil 3 Reward untuk menu cepat penukaran
    $availableRewards = \App\Models\PointReward::where('is_active', true)
        ->where(function($q) {
            $q->whereNull('stock')->orWhere('stock', '>', 0);
        })
        ->orderBy('points_required', 'asc')
        ->take(3)
        ->get();

    // 5. Ambil data Produk (Menu Favorit/Terbaru)
    // Menggunakan eager loading 'produk_detail' untuk mendapatkan harga
    $featuredProducts = \App\Models\Product::with(['details'])
        ->where('status', 'active')
        ->latest()
        ->take(4) // Ambil 4 produk saja untuk tampilan grid dashboard
        ->get();

    // 6. Kirim data ke view
    return view('pages.customers.index', [
        'customer'         => $customer,
        'transactions'     => $transactions,
        'nextReward'       => $nextReward,
        'availableRewards' => $availableRewards,
        'featuredProducts' => $featuredProducts, // Variabel baru untuk produk
    ]);
}

public function menu(Request $request)
{
    /** @var \App\Models\Customers $customer */
    $customer = Auth::guard('customers')->user();

    // Ambil semua kategori untuk filter
    $categories = \App\Models\Category::all(); // Pastikan Anda punya model Category

    // Query produk dengan filter
    $query = \App\Models\Product::with(['details'])->where('status', 'active');

    if ($request->has('category') && $request->category != 'all') {
        $query->whereHas('category', function($q) use ($request) {
            $q->where('name', $request->category);
        });
    }

    if ($request->has('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    $featuredProducts = $query->latest()->get();

    return view('pages.customers.menu', compact('customer', 'categories', 'featuredProducts'));
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

        // 2. Cek stok (Bypass jika NULL/unlimited)
        $isNonPhysical = in_array($reward->reward_type, ['voucher', 'other']);
        if (!$isNonPhysical && !is_null($reward->stock) && $reward->stock <= 0) {
            return back()->with('error', 'Maaf, stok hadiah ini sedang habis.');
        }

        try {
            DB::transaction(function () use ($customer, $reward, $isNonPhysical) {
                // 1. Buat Record Penukaran
                PointRedemption::create([
                    'redemption_code' => 'RDM-' . strtoupper(Str::random(10)),
                    'customer_id'     => $customer->id,
                    'point_reward_id' => $reward->id,
                    'points_used'     => $reward->points_required,
                    'status'          => 'pending'
                ]);

                // 2. Kurangi Poin Pelanggan
                $customer->decrement('total_points', $reward->points_required);

                // 3. Catat di Mutasi Poin
                LoyaltyPoints::create([
                    'customer_id' => $customer->id,
                    'amount'      => $reward->points_required, 
                    'description' => 'Tukar Reward: ' . $reward->name,
                    'type'        => 'redeem'
                ]);

                // 4. Kurangi Stok HANYA jika bukan non-fisik dan stok tidak NULL
                if (!$isNonPhysical && !is_null($reward->stock)) {
                    $reward->decrement('stock');
                }
            });

            return back()->with('success', 'Penukaran berhasil! Silahkan tunjukkan kode penukaran ke kasir.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem. Silahkan coba lagi.');
        }
    }
    /**
     * Promo: Berdasarkan segmentasi RFM
     */
    public function promotions()
    {
        $customer = Auth::guard('customers')->user();
        
        // Memuat relasi rfmScore untuk mendapatkan segment_name
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

    // Menggunakan query builder untuk mendukung filter
    $query = Transaction::with(['details.product_detail.product']) // Eager Loading agar tidak N+1
                ->where('customer_id', $customer->id);

    // Filter Pencarian Berdasarkan Nomor Invoice
    if ($request->filled('search')) {
        $query->where('invoice_number', 'like', '%' . $request->search . '%');
    }

    // Filter Berdasarkan Status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Filter Berdasarkan Periode (Hari)
    if ($request->filled('period')) {
        $days = (int) $request->period;
        $query->where('transaction_date', '>=', now()->subDays($days));
    }

    // Eksekusi dengan pagination
    $transactions = $query->latest('transaction_date')->paginate(10);

    // Tetap mengirimkan ke view yang sama
    return view('pages.customers.transactions', compact('transactions'));
}

public function transactionShow($id)
{
    /** @var \App\Models\Customers $customer */
    $customer = Auth::guard('customers')->user();

    $transaction = \App\Models\Transaction::with([
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
        
        // Eager load rfmScore untuk menampilkan segmentasi di header profil
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

    // Logika Upload menggunakan Helper uploadImage
    if ($request->hasFile('profile_photo')) {
        // Hapus foto lama jika ada
        if ($customer->profile_photo) {
            // Kita hilangkan prefix 'storage/' agar path-nya sesuai untuk penghapusan
            $oldPath = str_replace('storage/', '', $customer->profile_photo);
            Storage::disk('public')->delete($oldPath);
        }
        
        // Gunakan helper uploadImage
        $data['profile_photo'] = $this->uploadImage($request->file('profile_photo'), $request->name);
    }

    $customer->update($data);

    return back()->with('success', 'Profil Anda telah berhasil diperbarui.');
}

/**
 * Helper Upload Image yang disesuaikan
 */
private function uploadImage($image, $name)
{
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
    $filename = time() . '_' . $cleanName . '.' . $image->getClientOriginalExtension();
    
    // Simpan ke folder 'customers' di disk 'public'
    $path = $image->storeAs('customers', $filename, 'public');
    
    // Mengembalikan path lengkap dengan 'storage/' agar sesuai dengan cara pemanggilan di view
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