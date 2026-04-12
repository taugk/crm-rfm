<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PointRewardController extends Controller
{
    /**
     * Menampilkan daftar katalog hadiah.
     */
    public function index()
    {
        $rewards = PointReward::latest()->get();
        return view('pages.admin.loyalty.rewards.index', compact('rewards'));
    }

    /**
     * Menampilkan form tambah hadiah.
     */
    public function create()
    {
        return view('pages.admin.loyalty.rewards.create');
    }

    /**
     * Menyimpan hadiah baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reward_type' => 'required|in:product,voucher,other',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required_if:reward_type,product|nullable|integer|min:0',
            'value_amount' => 'required_if:reward_type,voucher|nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'required|boolean',
        ]);

        $data = $request->all();

        // Handle Upload Gambar
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }

        PointReward::create($data);

        return redirect()->route('admin.loyalty.rewards')
            ->with('success', 'Hadiah berhasil ditambahkan ke katalog.');
    }

    /**
     * Menampilkan detail hadiah (Opsional).
     */
    public function show(PointReward $reward)
    {
        return view('pages.admin.loyalty.rewards.show', compact('reward'));
    }

    /**
     * Menampilkan form edit hadiah.
     */
    public function edit(PointReward $reward)
    {
        return view('pages.admin.loyalty.rewards.edit', compact('reward'));
    }

    /**
     * Memperbarui data hadiah.
     */
    public function update(Request $request, PointReward $reward)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reward_type' => 'required|in:product,voucher,other',
            'points_required' => 'required|integer|min:1',
            'stock' => 'required_if:reward_type,product|nullable|integer|min:0',
            'value_amount' => 'required_if:reward_type,voucher|nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'required|boolean',
        ]);

        $data = $request->all();

        // Handle Update Gambar
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($reward->image) {
                Storage::disk('public')->delete($reward->image);
            }
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }

        $reward->update($data);

        return redirect()->route('admin.loyalty.rewards')
            ->with('success', 'Data hadiah berhasil diperbarui.');
    }

    /**
     * Menghapus hadiah dari database.
     */
    public function destroy(PointReward $reward)
    {
        // Hapus file gambar dari storage
        if ($reward->image) {
            Storage::disk('public')->delete($reward->image);
        }

        $reward->delete();

        return redirect()->route('admin.loyalty.rewards')
            ->with('success', 'Hadiah berhasil dihapus dari katalog.');
    }
}