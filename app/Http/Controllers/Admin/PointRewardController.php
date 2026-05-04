<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PointRewardController extends Controller
{
    /**
     * Menampilkan daftar katalog hadiah (Admin & Manager).
     */
    public function index()
    {
        $rewards = PointReward::latest()->get();
        $userRole = Auth::user()->role;
        
        return view('pages.admin.loyalty.rewards.index', compact('rewards', 'userRole'));
    }

    /**
     * Menampilkan form tambah hadiah (Admin & Manager).
     */
    public function create()
    {
        return view('pages.admin.loyalty.rewards.create');
    }

    /**
     * Menyimpan hadiah baru ke database (Admin & Manager).
     */
    public function store(Request $request)
    {
        $userRole = Auth::user()->role;
        
        $request->validate([
            'name' => 'required|string|max:255',
            'reward_type' => 'required|in:product,voucher,other',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required_if:reward_type,product|nullable|integer|min:0',
            'value_amount' => 'required_if:reward_type,voucher|nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $data = $request->all();

        // Handle Upload Gambar
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }

        PointReward::create($data);

        // Redirect berdasarkan role
        $redirectRoute = $userRole === 'admin' 
            ? 'admin.loyalty.rewards' 
            : 'manager.loyalty.rewards';

        return redirect()->route($redirectRoute)
            ->with('success', 'Hadiah berhasil ditambahkan ke katalog.');
    }

    /**
     * Menampilkan detail hadiah (Admin & Manager).
     */
    public function show(PointReward $reward)
    {
        // Hitung total penukaran untuk hadiah ini
        $redemptionsCount = $reward->redemptions()->count();
        $reward->redemptions_count = $redemptionsCount;
        
        return view('pages.admin.loyalty.rewards.show', compact('reward'));
    }

    /**
     * Menampilkan form edit hadiah (Admin & Manager - Full Access).
     */
    public function edit(PointReward $reward)
    {
        $userRole = Auth::user()->role;
        return view('pages.admin.loyalty.rewards.edit', compact('reward', 'userRole'));
    }

    /**
     * Memperbarui data hadiah (Admin & Manager - Full Access).
     */
    public function update(Request $request, PointReward $reward)
    {
        $userRole = Auth::user()->role;
        
        // Validasi untuk semua role (Admin & Manager)
        $request->validate([
            'name' => 'required|string|max:255',
            'reward_type' => 'required|in:product,voucher,other',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required_if:reward_type,product|nullable|integer|min:0',
            'value_amount' => 'required_if:reward_type,voucher|nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);
        
        $data = $request->all();
        
        // Handle Update Gambar (untuk semua role)
        if ($request->hasFile('image')) {
            if ($reward->image) {
                Storage::disk('public')->delete($reward->image);
            }
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }
        
        $reward->update($data);
        
        $message = 'Data hadiah berhasil diperbarui.';
        
        // Redirect berdasarkan role
        $redirectRoute = $userRole === 'admin' 
            ? 'admin.loyalty.rewards' 
            : 'manager.loyalty.rewards';

        return redirect()->route($redirectRoute)
            ->with('success', $message);
    }

    /**
     * Menghapus hadiah dari database (Hanya Admin).
     * Manager tidak bisa menghapus hadiah.
     */
    public function destroy(PointReward $reward)
    {
        $userRole = Auth::user()->role;
        
        // Hapus file gambar dari storage
        if ($reward->image) {
            Storage::disk('public')->delete($reward->image);
        }
        
        $reward->delete();
        
        // Redirect berdasarkan role
        $redirectRoute = $userRole === 'admin' 
            ? 'admin.loyalty.rewards' 
            : 'manager.loyalty.rewards';

        $message = 'Hadiah berhasil dihapus dari katalog.';
        
        return redirect()->route($redirectRoute)
            ->with('success', $message);
    }
}