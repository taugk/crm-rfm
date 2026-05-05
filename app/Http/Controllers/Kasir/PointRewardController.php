<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\PointReward;
use App\Models\PointRedemption;
use App\Models\Customers;
use App\Models\LoyaltyPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointRewardController extends Controller
{
    /**
     * Menampilkan halaman penukaran poin (Kasir)
     */
    public function index()
    {
        $rewards = PointReward::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('points_required', 'asc')
            ->get();
        
        $pendingRedemptions = PointRedemption::with(['customer', 'reward'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $completedRedemptions = PointRedemption::with(['customer', 'reward'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        $members = Customers::where('type', 'member')
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get(['id', 'name', 'phone', 'email', 'total_points', 'profile_photo']);
        
        return view('pages.kasir.point-rewards.index', compact('rewards', 'pendingRedemptions', 'completedRedemptions', 'members'));
    }
    
    /**
     * ==================== SUB MENU: DAFTAR HADIAH ====================
     */
    public function rewards()
    {
        $rewards = PointReward::where('is_active', true)
            ->orderBy('points_required', 'asc')
            ->paginate(12);
        
        return view('pages.kasir.point-rewards.rewards', compact('rewards'));
    }
    
    /**
     * ==================== SUB MENU: RIWAYAT PENUKARAN ====================
     */
    public function redeemHistory(Request $request)
    {
        $query = PointRedemption::with(['customer', 'reward']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        $redemptions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $redemptions
            ]);
        }
        
        return view('pages.kasir.point-rewards.redeem-history', compact('redemptions'));
    }
    
    /**
     * ==================== SUB MENU: PENDING REQUEST (KONFIRMASI) ====================
     */
    public function pendingRequests(Request $request)
    {
        $query = PointRedemption::with(['customer', 'reward'])
            ->where('status', 'pending');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('reward_id')) {
            $query->where('point_reward_id', $request->reward_id);
        }
        
        $pendingRedemptions = $query->orderBy('created_at', 'desc')->get();
        $rewards = PointReward::where('is_active', true)->get();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pendingRedemptions,
                'total_points' => $pendingRedemptions->sum('points_used'),
                'unique_customers' => $pendingRedemptions->unique('customer_id')->count()
            ]);
        }
        
        return view('pages.kasir.point-rewards.pending', compact('pendingRedemptions', 'rewards'));
    }
    
    /**
     * ==================== KONFIRMASI MASSAL ====================
     * NOTE: Poin sudah dikurangi saat member request, jadi di sini hanya update status dan stok
     */
    public function bulkConfirmRedemptions(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:point_redemptions,id'
        ]);
        
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($request->ids as $id) {
            try {
                DB::beginTransaction();
                
                $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
                
                if ($redemption->status !== 'pending') {
                    throw new \Exception('Status sudah ' . $redemption->status);
                }
                
                $reward = $redemption->reward;
                
                // Validasi stok
                if ($reward->stock <= 0) {
                    throw new \Exception("Stok hadiah {$reward->name} habis");
                }
                
                // Update status redemption (TIDAK KURANGI POIN LAGI)
                $redemption->status = 'completed';
                $redemption->admin_notes = "Dikonfirmasi oleh Kasir (Massal): " . auth()->user()->name;
                $redemption->save();
                
                // Kurangi stok reward
                $reward->decrement('stock');
                
                DB::commit();
                $results['success']++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][] = "ID {$id}: " . $e->getMessage();
                Log::error('Bulk confirm error: ' . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$results['success']} request berhasil dikonfirmasi, {$results['failed']} gagal",
            'data' => $results
        ]);
    }
    
    /**
     * ==================== BATALKAN MASSAL ====================
     * NOTE: Poin harus dikembalikan ke customer karena sudah dipotong sebelumnya
     */
    public function bulkCancelRedemptions(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:point_redemptions,id'
        ]);
        
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($request->ids as $id) {
            try {
                DB::beginTransaction();
                
                $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
                
                if ($redemption->status !== 'pending') {
                    throw new \Exception('Status sudah ' . $redemption->status);
                }
                
                $customer = $redemption->customer;
                $reward = $redemption->reward;
                
                // KEMBALIKAN POIN YANG SUDAH DIKURANGI
                $customer->increment('total_points', $redemption->points_used);
                
                // Catat pengembalian poin
                LoyaltyPoints::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => null,
                    'amount' => $redemption->points_used,
                    'description' => "Pembatalan penukaran reward: {$reward->name} oleh Kasir",
                    'type' => 'refund'
                ]);
                
                // Update status redemption
                $redemption->status = 'cancelled';
                $redemption->admin_notes = "Dibatalkan oleh Kasir (Massal): " . auth()->user()->name;
                $redemption->save();
                
                // Kembalikan stok reward (karena belum diambil)
                $reward->increment('stock');
                
                DB::commit();
                $results['success']++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][] = "ID {$id}: " . $e->getMessage();
                Log::error('Bulk cancel error: ' . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$results['success']} request berhasil dibatalkan, {$results['failed']} gagal",
            'data' => $results
        ]);
    }
    
    /**
     * Mencari customer untuk penukaran poin (AJAX)
     */
    public function searchCustomer(Request $request)
    {
        $query = $request->get('q');
        
        $customers = Customers::where('type', 'member')
            ->where('status', 'active')
            ->when($query, function($q) use ($query) {
                $q->where(function($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                });
            })
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'total_points', 'profile_photo']);
        
        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
    
    /**
     * Get rewards via AJAX
     */
    public function getRewards()
    {
        $rewards = PointReward::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('points_required', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rewards
        ]);
    }
    
    /**
     * Proses penukaran poin LANGSUNG (Kasir)
     * Status langsung 'completed', poin langsung berkurang
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reward_id' => 'required|exists:point_rewards,id'
        ]);
        
        try {
            DB::beginTransaction();
            
            $customer = Customers::findOrFail($request->customer_id);
            $reward = PointReward::findOrFail($request->reward_id);
            
            if ($customer->type !== 'member') {
                throw new \Exception('Hanya member yang dapat menukarkan poin');
            }
            
            if ($customer->total_points < $reward->points_required) {
                throw new \Exception('Poin tidak mencukupi');
            }
            
            if ($reward->stock <= 0) {
                throw new \Exception('Stok hadiah habis');
            }
            
            // Kurangi poin customer
            $customer->total_points -= $reward->points_required;
            $customer->save();
            
            // Catat pengurangan poin
            LoyaltyPoints::create([
                'customer_id' => $customer->id,
                'transaction_id' => null,
                'amount' => -$reward->points_required,
                'description' => "Penukaran poin untuk {$reward->name} (oleh Kasir: " . auth()->user()->name . ")",
                'type' => 'redeem'
            ]);
            
            // Catat redemption dengan status COMPLETED
            $redemption = PointRedemption::create([
                'redemption_code' => $this->generateRedemptionCode(),
                'customer_id' => $customer->id,
                'point_reward_id' => $reward->id,
                'points_used' => $reward->points_required,
                'status' => 'completed',
                'admin_notes' => "Penukaran langsung oleh Kasir: " . auth()->user()->name
            ]);
            
            // Kurangi stok
            $reward->decrement('stock');
            
            DB::commit();
            
            Log::info('Point redemption by kasir (immediate)', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'reward_name' => $reward->name,
                'points_used' => $reward->points_required,
                'kasir_id' => auth()->id(),
                'redemption_code' => $redemption->redemption_code
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Penukaran berhasil!',
                'remaining_points' => $customer->total_points,
                'redemption' => $redemption,
                'reward_name' => $reward->name,
                'redemption_code' => $redemption->redemption_code
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kasir Point Redemption Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Konfirmasi redemption pending (Kasir konfirmasi request dari member)
     * NOTE: Poin sudah dikurangi saat member request, jadi di sini hanya update status dan stok
     */
    public function confirmRedemption($id)
    {
        try {
            DB::beginTransaction();
            
            $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
            
            if ($redemption->status !== 'pending') {
                throw new \Exception('Status penukaran sudah ' . $redemption->status);
            }
            
            $reward = $redemption->reward;
            
            // Validasi stok
            if ($reward->stock <= 0) {
                throw new \Exception('Stok hadiah habis');
            }
            
            // ========== PENTING: JANGAN KURANGI POIN LAGI ==========
            // Poin sudah dikurangi saat member request
            // Cukup update status dan kurangi stok
            
            // Update status redemption
            $redemption->status = 'completed';
            $redemption->admin_notes = "Dikonfirmasi oleh Kasir: " . auth()->user()->name;
            $redemption->save();
            
            // Kurangi stok reward
            $reward->decrement('stock');
            
            DB::commit();
            
            Log::info('Redemption confirmed by kasir (points already deducted)', [
                'redemption_id' => $redemption->id,
                'redemption_code' => $redemption->redemption_code,
                'customer_id' => $redemption->customer_id,
                'reward_name' => $reward->name,
                'points_used' => $redemption->points_used,
                'kasir_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Penukaran berhasil dikonfirmasi!',
                'redemption_code' => $redemption->redemption_code
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kasir Confirm Redemption Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Batalkan redemption pending
     * NOTE: Poin harus dikembalikan ke customer karena sudah dipotong sebelumnya
     */
    public function cancelRedemption($id)
    {
        try {
            DB::beginTransaction();
            
            $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
            
            if ($redemption->status !== 'pending') {
                throw new \Exception('Status penukaran sudah ' . $redemption->status);
            }
            
            $customer = $redemption->customer;
            $reward = $redemption->reward;
            
            // ========== KEMBALIKAN POIN YANG SUDAH DIKURANGI ==========
            $customer->increment('total_points', $redemption->points_used);
            
            // Catat pengembalian poin
            LoyaltyPoints::create([
                'customer_id' => $customer->id,
                'transaction_id' => null,
                'amount' => $redemption->points_used,
                'description' => "Pembatalan penukaran reward: {$reward->name} oleh Kasir",
                'type' => 'refund'
            ]);
            
            // Update status redemption
            $redemption->status = 'cancelled';
            $redemption->admin_notes = "Dibatalkan oleh Kasir: " . auth()->user()->name;
            $redemption->save();
            
            // Kembalikan stok reward (karena belum diambil)
            $reward->increment('stock');
            
            DB::commit();
            
            Log::info('Redemption cancelled by kasir (points returned)', [
                'redemption_id' => $redemption->id,
                'redemption_code' => $redemption->redemption_code,
                'customer_id' => $customer->id,
                'points_returned' => $redemption->points_used,
                'kasir_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Penukaran berhasil dibatalkan. Poin dikembalikan ke member.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kasir Cancel Redemption Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Generate unique redemption code
     */
    private function generateRedemptionCode()
    {
        do {
            $code = 'RDM-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
        } while (PointRedemption::where('redemption_code', $code)->exists());
        
        return $code;
    }
    
    /**
     * Riwayat penukaran poin (Kasir) - Legacy method
     */
    public function history(Request $request)
    {
        return $this->redeemHistory($request);
    }
    
    /**
     * Detail redemption
     */
    public function showRedemption($id)
    {
        $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $redemption
        ]);
    }
    
    /**
     * Print voucher
     */
    public function printVoucher($id)
    {
        $redemption = PointRedemption::with(['customer', 'reward'])->findOrFail($id);
        
        if ($redemption->reward->reward_type !== 'voucher') {
            return redirect()->back()->with('error', 'Hanya reward tipe voucher yang bisa dicetak');
        }
        
        return view('pages.kasir.point-rewards.print-voucher', compact('redemption'));
    }
    
    /**
     * Get stats untuk dashboard
     */
    public function getStats()
    {
        $totalRedemptions = PointRedemption::count();
        $pendingRedemptions = PointRedemption::where('status', 'pending')->count();
        $completedRedemptions = PointRedemption::where('status', 'completed')->count();
        $totalPointsRedeemed = PointRedemption::sum('points_used');
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_redemptions' => $totalRedemptions,
                'pending' => $pendingRedemptions,
                'completed' => $completedRedemptions,
                'total_points_redeemed' => $totalPointsRedeemed
            ]
        ]);
    }
}