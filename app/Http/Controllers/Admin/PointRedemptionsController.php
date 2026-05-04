<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointRedemptionsController extends Controller
{
    

    /**
     * Display a listing of the resource (Admin & Manager).
     */
    public function index()
    {
        $userRole = Auth::user()->role;
        
        // Manager hanya bisa melihat redemption yang statusnya tertentu (opsional)
        // Atau bisa melihat semua tapi dengan batasan tertentu
        if ($userRole === 'manager') {
            // Manager bisa melihat semua redemption, tapi mungkin hanya yang belum selesai
            // Sesuaikan dengan kebutuhan bisnis
            $redemptions = PointRedemption::with(['customer', 'reward'])
                            ->whereIn('status', ['pending', 'processing']) // contoh filter
                            ->latest()
                            ->get();
        } else {
            // Admin melihat semua redemption
            $redemptions = PointRedemption::with(['customer', 'reward'])
                            ->latest()
                            ->get();
        }
        
        return view('pages.admin.loyalty.redeem.index', compact('redemptions', 'userRole'));
    }

    /**
     * Show the form for creating a new resource.
     * (Biasanya tidak digunakan karena redemption dibuat dari frontend)
     */
    public function create()
    {
        // Hanya admin yang bisa membuat redemption manual jika diperlukan
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access.');
        }
        
        return view('pages.admin.loyalty.redeem.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Hanya admin yang bisa membuat redemption manual
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'reward_id' => 'required|exists:point_rewards,id',
            'points_used' => 'required|integer|min:1',
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);
        
        $redemption = PointRedemption::create($request->all());
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', 'Redemption berhasil ditambahkan.');
    }

    /**
     * Display the specified resource (Admin & Manager).
     */
    public function show(PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        // Optional: Manager hanya bisa melihat redemption tertentu
        if ($userRole === 'manager' && in_array($pointRedemption->status, ['completed', 'cancelled'])) {
            abort(403, 'Manager tidak dapat melihat redemption yang sudah selesai/dibatalkan.');
        }
        
        return view('pages.admin.loyalty.redeem.show', compact('pointRedemption', 'userRole'));
    }

    /**
     * Show the form for editing the specified resource (Admin & Manager).
     */
    public function edit(PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        // Manager hanya bisa edit redemption dengan status tertentu
        if ($userRole === 'manager' && !in_array($pointRedemption->status, ['pending', 'processing'])) {
            abort(403, 'Manager hanya bisa mengedit redemption yang statusnya pending atau processing.');
        }
        
        return view('pages.admin.loyalty.redeem.edit', compact('pointRedemption', 'userRole'));
    }

    /**
     * Update the specified resource in storage (Admin & Manager dengan batasan).
     */
    public function update(Request $request, PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        // Aturan validasi berdasarkan role
        if ($userRole === 'manager') {
            // Manager hanya bisa update status tertentu
            $request->validate([
                'status' => 'required|in:pending,processing,completed',
                'notes' => 'nullable|string|max:500',
            ]);
            
            // Manager tidak bisa mengubah status dari completed/cancelled
            if (in_array($pointRedemption->status, ['completed', 'cancelled'])) {
                abort(403, 'Tidak dapat mengupdate redemption yang sudah selesai/dibatalkan.');
            }
            
            $data = [
                'status' => $request->status,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
                'notes' => $request->notes,
            ];
            
            // Jika status menjadi completed, catat waktu penyelesaian
            if ($request->status === 'completed') {
                $data['completed_at'] = now();
            }
            
        } else {
            // Admin bisa update semua field
            $request->validate([
                'customer_id' => 'sometimes|exists:users,id',
                'reward_id' => 'sometimes|exists:point_rewards,id',
                'points_used' => 'sometimes|integer|min:1',
                'status' => 'required|in:pending,processing,completed,cancelled',
                'notes' => 'nullable|string|max:500',
            ]);
            
            $data = $request->all();
            $data['updated_by'] = Auth::id();
            
            // Jika status berubah menjadi completed
            if ($request->status === 'completed' && !$pointRedemption->completed_at) {
                $data['completed_at'] = now();
            }
            
            // Jika status berubah menjadi cancelled
            if ($request->status === 'cancelled') {
                $data['cancelled_at'] = now();
                $data['cancelled_by'] = Auth::id();
            }
        }
        
        $pointRedemption->update($data);
        
        $message = $userRole === 'manager' 
            ? 'Status redemption berhasil diperbarui.' 
            : 'Data redemption berhasil diperbarui.';
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage (Hanya Admin).
     */
    public function destroy(PointRedemption $pointRedemption)
    {
        // Admin bisa menghapus redemption
        // Optional: hanya bisa menghapus yang statusnya pending atau cancelled
        if (!in_array($pointRedemption->status, ['pending', 'cancelled'])) {
            return redirect()->route('admin.loyalty.redemptions')
                ->with('error', 'Hanya redemption dengan status pending atau cancelled yang dapat dihapus.');
        }
        
        // Catat siapa yang menghapus sebelum delete
        $pointRedemption->update(['deleted_by' => Auth::id()]);
        
        $pointRedemption->delete();
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', 'Redemption berhasil dihapus.');
    }
    
    /**
     * Approve redemption (khusus untuk manager/Admin).
     */
    public function approve(PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        // Manager dan admin bisa approve
        if (!in_array($userRole, ['admin', 'manager'])) {
            abort(403);
        }
        
        // Hanya bisa approve jika status pending
        if ($pointRedemption->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Hanya redemption dengan status pending yang dapat disetujui.');
        }
        
        $pointRedemption->update([
            'status' => 'processing',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', 'Redemption berhasil disetujui dan sedang diproses.');
    }
    
    /**
     * Complete redemption (khusus untuk manager/Admin).
     */
    public function complete(PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        if (!in_array($userRole, ['admin', 'manager'])) {
            abort(403);
        }
        
        // Hanya bisa complete jika status processing
        if ($pointRedemption->status !== 'processing') {
            return redirect()->back()
                ->with('error', 'Hanya redemption dengan status processing yang dapat diselesaikan.');
        }
        
        $pointRedemption->update([
            'status' => 'completed',
            'completed_by' => Auth::id(),
            'completed_at' => now(),
        ]);
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', 'Redemption berhasil diselesaikan.');
    }
    
    /**
     * Cancel redemption (khusus untuk admin/manager).
     */
    public function cancel(PointRedemption $pointRedemption)
    {
        $userRole = Auth::user()->role;
        
        if (!in_array($userRole, ['admin', 'manager'])) {
            abort(403);
        }
        
        // Hanya bisa cancel jika status pending atau processing
        if (!in_array($pointRedemption->status, ['pending', 'processing'])) {
            return redirect()->back()
                ->with('error', 'Hanya redemption dengan status pending/processing yang dapat dibatalkan.');
        }
        
        $pointRedemption->update([
            'status' => 'cancelled',
            'cancelled_by' => Auth::id(),
            'cancelled_at' => now(),
        ]);
        
        return redirect()->route('admin.loyalty.redemptions')
            ->with('success', 'Redemption berhasil dibatalkan.');
    }
}