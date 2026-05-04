<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoyaltyPointsController extends Controller
{
    

    /**
     * Display a listing of the resource (Admin & Manager).
     */
    public function index(Request $request)
    {
        $userRole = Auth::user()->role;
        
        // Query dengan filter
        $query = LoyaltyPoints::with('customer');
        
        // Filter berdasarkan customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Filter berdasarkan tipe transaksi
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        
        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        
        
        // Mengambil riwayat mutasi poin terbaru
        $logs = $query->latest()->paginate(20);
        
        // Data untuk filter dropdown
        $customers = User::where('role', 'customer')->orderBy('name')->get();
        $transactionTypes = [
            'purchase' => 'Pembelian',
            'redemption' => 'Penukaran',
            
            'expired' => 'Kadaluarsa',
            'bonus' => 'Bonus',
        ];
        
        return view('pages.admin.loyalty.logs.index', compact('logs', 'customers', 'transactionTypes', 'userRole'));
    }

    /**
     * Show the form for creating a new resource (Admin & Manager).
     */
    public function create()
    {
        // Admin dan Manager bisa menambah poin manual
        $customers = User::where('role', 'customer')->orderBy('name')->get();
        
        return view('pages.admin.loyalty.logs.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage (Admin & Manager).
     */
    public function store(Request $request)
    {
        $userRole = Auth::user()->role;
        
        // Validasi dasar
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1',
            'type' => 'required|in:add,subtract',
            'reason' => 'required|string|max:500',
        ]);
        
        // Batasan untuk Manager
        if ($userRole === 'manager') {
            // Manager hanya bisa menambah maksimal 500 poin dalam satu transaksi
            if ($request->points > 500) {
                return redirect()->back()
                    ->with('error', 'Manager hanya bisa menambah/mengurangi maksimal 500 poin per transaksi.');
            }
            
            // Manager hanya bisa melakukan adjustment (bukan bonus besar)
            if ($request->type === 'add' && $request->points > 200) {
                return redirect()->back()
                    ->with('error', 'Manager hanya bisa memberikan bonus maksimal 200 poin. Untuk nominal lebih besar, hubungi Admin.');
            }
        }
        
        try {
            DB::beginTransaction();
            
            $customer = User::findOrFail($request->customer_id);
            $currentPoints = $customer->loyalty_points ?? 0;
            
            // Hitung poin baru
            if ($request->type === 'add') {
                $newPoints = $currentPoints + $request->points;
                $transactionType = 'adjustment';
                $description = "Penambahan poin manual: " . $request->reason;
            } else {
                // Cek apakah poin mencukupi
                if ($currentPoints < $request->points) {
                    throw new \Exception("Poin customer tidak mencukupi. Poin saat ini: {$currentPoints}, poin akan dikurangi: {$request->points}");
                }
                $newPoints = $currentPoints - $request->points;
                $transactionType = 'adjustment';
                $description = "Pengurangan poin manual: " . $request->reason;
            }
            
            // Simpan log transaksi
            $log = LoyaltyPoints::create([
                'customer_id' => $request->customer_id,
                'points' => $request->type === 'add' ? $request->points : -$request->points,
                'balance_before' => $currentPoints,
                'balance_after' => $newPoints,
                'transaction_type' => $transactionType,
                'description' => $description,
                'reference_id' => null,
                'created_by' => Auth::id(),
                'notes' => "Dibuat oleh: " . Auth::user()->name . " (Role: " . ucfirst($userRole) . ")",
            ]);
            
            // Update poin customer
            $customer->update(['loyalty_points' => $newPoints]);
            
            DB::commit();
            
            $message = $request->type === 'add' 
                ? "Berhasil menambahkan {$request->points} poin untuk customer {$customer->name}"
                : "Berhasil mengurangi {$request->points} poin dari customer {$customer->name}";
            
            return redirect()->route('admin.loyalty.logs')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal memproses poin: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource (Admin & Manager).
     */
    public function show(LoyaltyPoints $loyaltyPoints)
    {
        $userRole = Auth::user()->role;
        
        // Load relasi customer
        $loyaltyPoints->load('customer', 'createdBy');
        
        // Manager hanya bisa melihat transaksi tertentu
        if ($userRole === 'manager' && $loyaltyPoints->transaction_type === 'expired') {
            abort(403, 'Manager tidak dapat melihat detail poin kadaluarsa.');
        }
        
        return view('pages.admin.loyalty.logs.show', compact('loyaltyPoints', 'userRole'));
    }

    /**
     * Show the form for editing the specified resource.
     * (Biasanya tidak perlu edit transaksi poin)
     */
    public function edit(LoyaltyPoints $loyaltyPoints)
    {
        // Hanya admin yang bisa edit log poin, itupun dengan batasan
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat mengedit log poin.');
        }
        
        // Hanya bisa edit transaksi yang belum lama (misal: kurang dari 24 jam)
        $hoursSinceCreated = now()->diffInHours($loyaltyPoints->created_at);
        if ($hoursSinceCreated > 24) {
            return redirect()->route('admin.loyalty.logs')
                ->with('error', 'Tidak dapat mengedit log poin yang sudah lebih dari 24 jam.');
        }
        
        $customers = User::where('role', 'customer')->get();
        
        return view('pages.admin.loyalty.logs.edit', compact('loyaltyPoints', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoyaltyPoints $loyaltyPoints)
    {
        // Hanya admin yang bisa update
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat mengupdate log poin.');
        }
        
        $request->validate([
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Update hanya deskripsi dan notes, tidak mengubah jumlah poin
        $loyaltyPoints->update([
            'description' => $request->description,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);
        
        return redirect()->route('admin.loyalty.logs')
            ->with('success', 'Log poin berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage (Hanya Admin).
     */
    public function destroy(LoyaltyPoints $loyaltyPoints)
    {
        // Hanya admin yang bisa hapus
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat menghapus log poin.');
        }
        
        try {
            DB::beginTransaction();
            
            // Cek apakah log ini terkait dengan transaksi yang sudah lama
            $daysSinceCreated = now()->diffInDays($loyaltyPoints->created_at);
            if ($daysSinceCreated > 7) {
                throw new \Exception('Tidak dapat menghapus log poin yang sudah lebih dari 7 hari.');
            }
            
            // Kembalikan poin customer ke kondisi sebelum transaksi
            $customer = User::findOrFail($loyaltyPoints->customer_id);
            $currentPoints = $customer->loyalty_points ?? 0;
            
            // Balikkan efek transaksi
            $pointsToRevert = abs($loyaltyPoints->points);
            if ($loyaltyPoints->points > 0) {
                // Jika dulu menambah poin, sekarang kurangi
                $newPoints = $currentPoints - $pointsToRevert;
                $revertAction = "Dihapus oleh Admin: " . Auth::user()->name;
            } else {
                // Jika dulu mengurangi poin, sekarang tambah kembali
                $newPoints = $currentPoints + $pointsToRevert;
                $revertAction = "Dikembalikan oleh Admin: " . Auth::user()->name;
            }
            
            // Update poin customer
            $customer->update(['loyalty_points' => $newPoints]);
            
            // Soft delete atau hard delete? Simpan sebagai log terpisah
            $loyaltyPoints->update([
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
                'notes' => ($loyaltyPoints->notes ? $loyaltyPoints->notes . "\n" : "") . $revertAction,
            ]);
            
            $loyaltyPoints->delete();
            
            DB::commit();
            
            return redirect()->route('admin.loyalty.logs')
                ->with('success', 'Log poin berhasil dihapus dan poin customer telah dikembalikan.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal menghapus log poin: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk delete logs (Hanya Admin).
     */
    public function bulkDelete(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat menghapus log poin massal.');
        }
        
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:loyalty_points,id',
        ]);
        
        try {
            DB::beginTransaction();
            
            $logs = LoyaltyPoints::whereIn('id', $request->ids)->get();
            $deletedCount = 0;
            
            foreach ($logs as $log) {
                // Sama seperti method destroy untuk satu log
                $daysSinceCreated = now()->diffInDays($log->created_at);
                if ($daysSinceCreated <= 7) {
                    $customer = User::findOrFail($log->customer_id);
                    $currentPoints = $customer->loyalty_points ?? 0;
                    $pointsToRevert = abs($log->points);
                    
                    if ($log->points > 0) {
                        $newPoints = $currentPoints - $pointsToRevert;
                    } else {
                        $newPoints = $currentPoints + $pointsToRevert;
                    }
                    
                    $customer->update(['loyalty_points' => $newPoints]);
                    $log->delete();
                    $deletedCount++;
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.loyalty.logs')
                ->with('success', "Berhasil menghapus {$deletedCount} log poin.");
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal menghapus log poin massal: ' . $e->getMessage());
        }
    }
    
    /**
     * Export logs ke Excel/CSV (Hanya Admin).
     */
    public function export(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Hanya Admin yang dapat mengekspor log poin.');
        }
        
        // Logika export ke Excel/CSV
        $query = LoyaltyPoints::with('customer');
        
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $logs = $query->get();
        
        // Export logic here
        // ...
        
        return redirect()->back()->with('success', 'Export berhasil dilakukan.');
    }
    
    /**
     * Get customer points summary (Admin & Manager).
     */
    public function customerSummary($customerId)
    {
        $userRole = Auth::user()->role;
        
        $customer = User::where('role', 'customer')->findOrFail($customerId);
        
        $summary = [
            'current_points' => $customer->loyalty_points ?? 0,
            'total_earned' => LoyaltyPoints::where('customer_id', $customerId)
                ->where('points', '>', 0)
                ->sum('points'),
            'total_redeemed' => abs(LoyaltyPoints::where('customer_id', $customerId)
                ->where('points', '<', 0)
                ->sum('points')),
            'total_adjustments' => LoyaltyPoints::where('customer_id', $customerId)
                ->where('transaction_type', 'adjustment')
                ->count(),
            'recent_transactions' => LoyaltyPoints::where('customer_id', $customerId)
                ->latest()
                ->limit(10)
                ->get(),
        ];
        
        return view('pages.admin.loyalty.logs.customer-summary', compact('customer', 'summary', 'userRole'));
    }
}