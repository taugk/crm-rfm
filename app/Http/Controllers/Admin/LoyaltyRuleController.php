<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoyaltyRuleController extends Controller
{
    

    /**
     * Menampilkan daftar aturan poin (Admin & Manager).
     */
    public function index()
    {
        $userRole = Auth::user()->role;
        $rules = LoyaltyRule::orderBy('min_purchase', 'asc')->get();
        
        return view('pages.admin.loyalty.rules.index', compact('rules', 'userRole'));
    }

    /**
     * Menyimpan aturan baru (Hanya Admin).
     */
    public function store(Request $request)
    {
        

        $request->validate([
            'rule_name' => 'required|string|max:255',
            'min_purchase' => 'required|numeric|min:0',
            'points_earned' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Cek apakah ada aturan dengan min_purchase yang sama
            $existingRule = LoyaltyRule::where('min_purchase', $request->min_purchase)->first();
            if ($existingRule) {
                throw new \Exception('Aturan dengan nominal pembelian minimal ' . number_format($request->min_purchase) . ' sudah ada.');
            }

            // Opsional: Jika aturan baru ini aktif, nonaktifkan aturan lain yang sedang aktif
            // agar tidak terjadi tumpang tindih perhitungan poin.
            if ($request->has('is_active') && $request->is_active == 1) {
                LoyaltyRule::where('is_active', true)->update(['is_active' => false]);
            }

            LoyaltyRule::create([
                'rule_name' => $request->rule_name,
                'min_purchase' => $request->min_purchase,
                'points_earned' => $request->points_earned,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? $request->is_active : true,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Aturan poin baru berhasil ditambahkan.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menambahkan aturan: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui aturan (Admin: semua field, Manager: hanya status aktif).
     */
    public function update(Request $request, $id)
    {
        $userRole = Auth::user()->role;
        
        try {
            DB::beginTransaction();

            $rule = LoyaltyRule::findOrFail($id);

            // Validasi berdasarkan role
            if ($userRole === 'manager') {
                // Manager hanya bisa mengubah status aktif
                $request->validate([
                    'is_active' => 'required|boolean',
                ]);
                
                // Manager tidak bisa mengaktifkan aturan jika ada aturan lain yang sudah aktif
                if ($request->is_active == 1) {
                    $activeRuleExists = LoyaltyRule::where('id', '!=', $id)
                        ->where('is_active', true)
                        ->exists();
                    
                    if ($activeRuleExists) {
                        throw new \Exception('Tidak dapat mengaktifkan aturan ini karena sudah ada aturan lain yang aktif. Hubungi Admin untuk mengubah aturan aktif.');
                    }
                }
                
                $data = [
                    'is_active' => $request->is_active,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ];
                
                $message = 'Status aturan poin berhasil diperbarui.';
                
            } else {
                // Admin bisa update semua field
                $request->validate([
                    'rule_name' => 'required|string|max:255',
                    'min_purchase' => 'required|numeric|min:0',
                    'points_earned' => 'required|integer|min:1',
                    'description' => 'nullable|string|max:500',
                    'is_active' => 'required|boolean',
                ]);
                
                // Cek duplikasi min_purchase (kecuali untuk aturan yang sedang diedit)
                $existingRule = LoyaltyRule::where('min_purchase', $request->min_purchase)
                    ->where('id', '!=', $id)
                    ->first();
                    
                if ($existingRule) {
                    throw new \Exception('Aturan dengan nominal pembelian minimal ' . number_format($request->min_purchase) . ' sudah ada.');
                }
                
                // Jika aturan ini diaktifkan, matikan aturan aktif lainnya
                if ($request->is_active == 1) {
                    LoyaltyRule::where('id', '!=', $id)->update(['is_active' => false]);
                }
                
                $data = $request->all();
                $data['updated_by'] = Auth::id();
                
                $message = 'Aturan poin berhasil diperbarui.';
            }
            
            $rule->update($data);
            
            DB::commit();
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal memperbarui aturan: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus aturan (Hanya Admin).
     */
    public function destroy($id)
    {
        // Pastikan hanya admin yang bisa akses
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat menghapus aturan.');
        }
        
        try {
            DB::beginTransaction();
            
            $rule = LoyaltyRule::findOrFail($id);
            
            // Cek apakah aturan ini sedang aktif
            if ($rule->is_active) {
                throw new \Exception('Tidak dapat menghapus aturan yang sedang aktif. Nonaktifkan aturan terlebih dahulu.');
            }
            
            // Soft delete atau hard delete? Gunakan soft delete lebih aman
            $rule->deleted_by = Auth::id();
            $rule->save();
            $rule->delete(); // Jika menggunakan SoftDelete, panggil delete()
            
            DB::commit();
            return redirect()->back()->with('success', 'Aturan berhasil dihapus.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menghapus aturan: ' . $e->getMessage());
        }
    }
    
    /**
     * Mengaktifkan aturan tertentu (Admin & Manager).
     */
    public function activate($id)
    {
        $userRole = Auth::user()->role;
        
        try {
            DB::beginTransaction();
            
            $rule = LoyaltyRule::findOrFail($id);
            
            // Manager tidak bisa mengaktifkan aturan sendiri tanpa admin
            if ($userRole === 'manager') {
                $activeRuleCount = LoyaltyRule::where('is_active', true)->count();
                if ($activeRuleCount > 0) {
                    throw new \Exception('Hanya Admin yang dapat mengganti aturan yang sedang aktif.');
                }
            }
            
            // Nonaktifkan semua aturan yang aktif
            LoyaltyRule::where('is_active', true)->update(['is_active' => false]);
            
            // Aktifkan aturan yang dipilih
            $rule->update([
                'is_active' => true,
                'activated_by' => Auth::id(),
                'activated_at' => now(),
            ]);
            
            DB::commit();
            return redirect()->back()->with('success', 'Aturan "' . $rule->rule_name . '" berhasil diaktifkan.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal mengaktifkan aturan: ' . $e->getMessage());
        }
    }
    
    /**
     * Menonaktifkan aturan (Admin & Manager).
     */
    public function deactivate($id)
    {
        $rule = LoyaltyRule::findOrFail($id);
        
        // Cek apakah ini satu-satunya aturan yang aktif
        $activeRuleCount = LoyaltyRule::where('is_active', true)->count();
        
        if ($activeRuleCount == 1 && $rule->is_active) {
            return redirect()->back()->with('error', 'Tidak dapat menonaktifkan aturan terakhir yang aktif. Minimal harus ada satu aturan aktif.');
        }
        
        $rule->update([
            'is_active' => false,
            'deactivated_by' => Auth::id(),
            'deactivated_at' => now(),
        ]);
        
        return redirect()->back()->with('success', 'Aturan "' . $rule->rule_name . '" berhasil dinonaktifkan.');
    }
    
    /**
     * Duplikasi aturan (Hanya Admin).
     */
    public function duplicate($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat menduplikasi aturan.');
        }
        
        try {
            DB::beginTransaction();
            
            $originalRule = LoyaltyRule::findOrFail($id);
            
            // Buat aturan baru dengan data yang sama
            $newRule = $originalRule->replicate();
            $newRule->rule_name = $originalRule->rule_name . ' (Copy)';
            $newRule->is_active = false; // Aturan duplikasi tidak aktif secara default
            $newRule->created_by = Auth::id();
            $newRule->created_at = now();
            $newRule->save();
            
            DB::commit();
            return redirect()->back()->with('success', 'Aturan berhasil diduplikasi. Silakan edit aturan duplikasi sesuai kebutuhan.');
            
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal menduplikasi aturan: ' . $e->getMessage());
        }
    }
}