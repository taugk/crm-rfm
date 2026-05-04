<?php


namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers;
use App\Models\Transaction;
use App\Models\LoyaltyPoints;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index()
    {
        $customers = Customers::where('status', 'active')
            ->orderBy('type', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(20);
        
        return view('pages.kasir.customers.index', compact('customers'));
    }
    
    /**
     * Show create member form
     */
    public function create()
    {
        return view('pages.kasir.customers.create');
    }
    
    /**
     * Store new member
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'full_address' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            $customer = Customers::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'full_address' => $request->full_address,
                'type' => 'member',
                'role' => 'customer',
                'status' => 'active',
                'total_points' => 0
            ]);
            
            DB::commit();
            
            return redirect()->route('kasir.members.check')
                ->with('success', "Member {$customer->name} berhasil ditambahkan");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating customer: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal menambahkan member: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Check member page (search page)
     */
    public function check()
    {
        return view('pages.kasir.members.check');
    }
    
    /**
     * Search customer via AJAX (GET method)
     */
    public function search(Request $request)
    {
        $search = $request->get('q', $request->get('search', ''));
        
        if (strlen($search) < 2) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal 2 karakter',
                    'data' => []
                ]);
            }
            return redirect()->route('kasir.members.check');
        }
        
        $customers = Customers::where('status', 'active')
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderByRaw("CASE WHEN type = 'member' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'type' => $customer->type,
                    'total_points' => (int)($customer->total_points ?? 0),
                    'status' => $customer->status
                ];
            });
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        }
        
        return view('pages.kasir.members.check', compact('customers'));
    }
    
    /**
     * Show customer detail
     */
    public function show($id)
    {
        try {
            $customer = Customers::with([
                'transactions' => function($query) {
                    $query->orderBy('transaction_date', 'desc')->limit(10);
                }, 
                'loyaltyPoints' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(10);
                }
            ])->findOrFail($id);
            
            // Calculate statistics
            $totalSpent = $customer->transactions()->where('status', 'completed')->sum('total_price') ?? 0;
            $totalTransactions = $customer->transactions()->where('status', 'completed')->count();
            $lastTransaction = $customer->transactions()->where('status', 'completed')
                ->latest('transaction_date')
                ->first();
            
            // Get points history summary
            $pointsEarned = $customer->loyaltyPoints()->where('type', 'earn')->sum('amount') ?? 0;
            $pointsRedeemed = $customer->loyaltyPoints()->where('type', 'redeem')->sum('amount') ?? 0;
            
            return view('pages.kasir.customers.show', compact(
                'customer', 
                'totalSpent', 
                'totalTransactions', 
                'lastTransaction',
                'pointsEarned',
                'pointsRedeemed'
            ));
        } catch (\Exception $e) {
            Log::error('Error showing customer: ' . $e->getMessage());
            return redirect()->route('kasir.members.check')
                ->with('error', 'Member tidak ditemukan');
        }
    }
    
    /**
     * Get customer detail for POS (AJAX)
     */
    public function getDetail($id)
    {
        try {
            $customer = Customers::findOrFail($id);
            
            // Points conversion (1 point = Rp 10)
            $pointsValue = ($customer->total_points ?? 0) * 10;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'type' => $customer->type,
                    'total_points' => (int)($customer->total_points ?? 0),
                    'points_value' => $pointsValue,
                    'status' => $customer->status,
                    'last_purchase_at' => $customer->last_purchase_at ? $customer->last_purchase_at->format('d/m/Y H:i') : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }
    }
    
    /**
     * Show edit form
     */
    public function edit($id)
    {
        try {
            $customer = Customers::findOrFail($id);
            return view('pages.kasir.customers.edit', compact('customer'));
        } catch (\Exception $e) {
            return redirect()->route('kasir.members.index')
                ->with('error', 'Member tidak ditemukan');
        }
    }
    
    /**
     * Update customer
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone,' . $id,
            'email' => 'nullable|email|unique:customers,email,' . $id,
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'full_address' => 'nullable|string'
        ]);
        
        try {
            $customer = Customers::findOrFail($id);
            $customer->update($request->all());
            
            return redirect()->route('kasir.members.show', $customer->id)
                ->with('success', 'Member berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengupdate member')
                ->withInput();
        }
    }
    
    /**
     * Get customer transaction history (AJAX)
     */
    public function getHistory($id)
    {
        try {
            $customer = Customers::findOrFail($id);
            $transactions = $customer->transactions()
                ->where('status', 'completed')
                ->orderBy('transaction_date', 'desc')
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }
}