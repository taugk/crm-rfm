<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyRuleController extends Controller
{
    /**
     * Menampilkan daftar aturan poin.
     */
    public function index()
    {
        $rules = LoyaltyRule::orderBy('created_at', 'desc')->get();
        return view('pages.admin.loyalty.rules.index', compact('rules'));
    }

    /**
     * Menyimpan aturan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rule_name' => 'required|string|max:255',
            'min_purchase' => 'required|numeric|min:0',
            'points_earned' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Opsional: Jika aturan baru ini aktif, nonaktifkan aturan lain yang sedang aktif
            // agar tidak terjadi tumpang tindih perhitungan poin.
            if ($request->has('is_active') && $request->is_active == 1) {
                LoyaltyRule::where('is_active', true)->update(['is_active' => false]);
            }

            LoyaltyRule::create([
                'rule_name' => $request->rule_name,
                'min_purchase' => $request->min_purchase,
                'points_earned' => $request->points_earned,
                'is_active' => $request->has('is_active') ? $request->is_active : true,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Aturan poin baru berhasil ditambahkan.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menambahkan aturan: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui aturan (Edit via Modal).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'rule_name' => 'required|string|max:255',
            'min_purchase' => 'required|numeric|min:0',
            'points_earned' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $rule = LoyaltyRule::findOrFail($id);

            // Jika aturan ini diaktifkan, matikan aturan aktif lainnya
            if ($request->is_active == 1) {
                LoyaltyRule::where('id', '!=', $id)->update(['is_active' => false]);
            }

            $rule->update($request->all());

            DB::commit();
            return redirect()->back()->with('success', 'Aturan poin berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal memperbarui aturan.');
        }
    }

    /**
     * Menghapus aturan.
     */
    public function destroy($id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Aturan berhasil dihapus.');
    }
}