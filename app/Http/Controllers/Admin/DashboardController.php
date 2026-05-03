<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Customers;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Promotions;
use App\Models\LoyaltyPoints;
use App\Models\PointReward;
use App\Models\PointRedemption;
use App\Models\RfmCalculationBatch;
use App\Models\RfmScore;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Constructor - Middleware untuk auth dan role admin
     */
    

    /**
     * Main Dashboard View
     */
    public function index()
    {
        // ==================== STATISTIK UTAMA ====================
        $totalUsers = User::count();
        $totalCustomers = Customers::count();
        $totalActiveCustomers = Customers::where('status', 'active')->count();
        $totalCategories = Category::count();
        $totalProducts = Product::count();
        $totalActiveProducts = Product::where('status', 'active')->count();
        
        // ==================== STOK ====================
        $totalStock = ProductDetail::sum('stock');
        $lowStockProducts = ProductDetail::with('product')
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->count();
        $outOfStockProducts = ProductDetail::where('stock', 0)->count();
        
        // ==================== TRANSAKSI ====================
        $totalTransactions = Transaction::count();
        $totalRevenue = Transaction::where('status', 'completed')->sum('total_price');
        $todayRevenue = Transaction::where('status', 'completed')
            ->whereDate('transaction_date', Carbon::today())
            ->sum('total_price');
        $thisMonthRevenue = Transaction::where('status', 'completed')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('total_price');
        
        // Transaksi hari ini
        $todayTransactions = Transaction::whereDate('transaction_date', Carbon::today())
            ->with('customer')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
        
        // ==================== PROMOSI ====================
        $activePromotions = Promotions::where('is_active', true)
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->count();
        
        $upcomingPromotions = Promotions::where('is_active', true)
            ->where('start_date', '>', Carbon::now())
            ->count();
        
        // ==================== LOYALTY & POIN ====================
        $totalPointsEarned = LoyaltyPoints::where('type', 'earn')->sum('amount');
        $totalPointsRedeemed = LoyaltyPoints::where('type', 'redeem')->sum('amount');
        $availableRewards = PointReward::where('is_active', true)
            ->where('stock', '>', 0)
            ->count();
        
        $pendingRedemptions = PointRedemption::where('status', 'pending')->count();
        
        // Customer dengan poin terbanyak
        $topPointCustomers = Customers::orderBy('total_points', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'total_points', 'phone']);
        
        // ==================== RFM / SEGMENTASI ====================
        $latestRfmBatch = RfmCalculationBatch::with('triggeredBy')
            ->orderBy('id', 'desc')
            ->first();
        
        $rfmSegmentStats = [];
        if ($latestRfmBatch) {
            $rfmSegmentStats = RfmScore::where('calculation_batch_id', $latestRfmBatch->id)
                ->select('segment_name', DB::raw('count(*) as total'))
                ->groupBy('segment_name')
                ->get()
                ->pluck('total', 'segment_name')
                ->toArray();
        }
        
        // ==================== GRAFIK PENJUALAN (7 HARI TERAKHIR) ====================
        $salesChart = $this->getSalesChartData();
        
        // ==================== GRAFIK PRODUK TERLARIS ====================
        $topProducts = $this->getTopProducts();
        
        // ==================== GRAFIK KATEGORI TERLARIS ====================
        $topCategories = $this->getTopCategories();
        
        // ==================== PENDAPATAN BULANAN ====================
        $monthlyRevenue = $this->getMonthlyRevenue();
        
        // ==================== STATISTIK PELANGGAN ====================
        $customerGrowth = $this->getCustomerGrowth();
        
        return view('pages.admin.index', compact(
            'totalUsers',
            'totalCustomers',
            'totalActiveCustomers',
            'totalCategories',
            'totalProducts',
            'totalActiveProducts',
            'totalStock',
            'lowStockProducts',
            'outOfStockProducts',
            'totalTransactions',
            'totalRevenue',
            'todayRevenue',
            'thisMonthRevenue',
            'todayTransactions',
            'activePromotions',
            'upcomingPromotions',
            'totalPointsEarned',
            'totalPointsRedeemed',
            'availableRewards',
            'pendingRedemptions',
            'topPointCustomers',
            'latestRfmBatch',
            'rfmSegmentStats',
            'salesChart',
            'topProducts',
            'topCategories',
            'monthlyRevenue',
            'customerGrowth'
        ));
    }
    
    /**
     * Get sales chart data for last 7 days
     */
    private function getSalesChartData()
    {
        $labels = [];
        $sales = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d M');
            
            $total = Transaction::where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->sum('total_price');
            
            $sales[] = (float) $total;
        }
        
        return [
            'labels' => $labels,
            'sales' => $sales
        ];
    }
    
    /**
     * Get top 5 best selling products
     */
    private function getTopProducts()
    {
        return TransactionDetail::select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(transactions_details.quantity) as total_sold'),
                DB::raw('SUM(transactions_details.subtotal) as total_revenue')
            )
            ->join('product_details', 'transactions_details.product_detail_id', '=', 'product_details.id')
            ->join('products', 'product_details.product_id', '=', 'products.id')
            ->join('transactions', 'transactions_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
    }
    
    /**
     * Get top 5 best selling categories
     */
    private function getTopCategories()
    {
        return Category::select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(transactions_details.quantity) as total_sold'),
                DB::raw('SUM(transactions_details.subtotal) as total_revenue')
            )
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('product_details', 'products.id', '=', 'product_details.product_id')
            ->join('transactions_details', 'product_details.id', '=', 'transactions_details.product_detail_id')
            ->join('transactions', 'transactions_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();
    }
    
    /**
     * Get monthly revenue for current year
     */
    private function getMonthlyRevenue()
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $revenues = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $total = Transaction::where('status', 'completed')
                ->whereMonth('transaction_date', $i)
                ->whereYear('transaction_date', Carbon::now()->year)
                ->sum('total_price');
            
            $revenues[] = (float) $total;
        }
        
        return [
            'labels' => $months,
            'revenues' => $revenues
        ];
    }
    
    /**
     * Get customer growth per month
     */
    private function getCustomerGrowth()
    {
        $labels = [];
        $counts = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            $total = Customers::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $counts[] = $total;
        }
        
        return [
            'labels' => $labels,
            'counts' => $counts
        ];
    }
    
    // ==================== API ENDPOINTS FOR AJAX ====================
    
    /**
     * Get sales data for specific date range (AJAX)
     */
    public function getSalesData(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        $transactions = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total_price) as total_revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
    
    /**
     * Get low stock products (AJAX)
     */
    public function getLowStockProducts()
    {
        $products = ProductDetail::with('product')
            ->where('stock', '<=', 5)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get()
            ->map(function($detail) {
                return [
                    'id' => $detail->id,
                    'product_name' => $detail->product->name,
                    'sku' => $detail->product->sku,
                    'variant' => $detail->variant,
                    'stock' => $detail->stock,
                    'url' => route('admin.products.show', $detail->product_id)
                ];
            });
        
        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
    
    /**
     * Get recent transactions (AJAX)
     */
    public function getRecentTransactions(Request $request)
    {
        $limit = $request->get('limit', 20);
        
        $transactions = Transaction::with('customer')
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'customer_name' => $transaction->customer->name ?? 'Walk In',
                    'total_price' => $transaction->total_price,
                    'status' => $transaction->status,
                    'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i:s')
                ];
            });
        
        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }
    
    /**
     * Get dashboard summary (for real-time updates)
     */
    public function getSummary()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => Customer::count(),
                'total_revenue' => Transaction::where('status', 'completed')->sum('total_price'),
                'today_revenue' => Transaction::where('status', 'completed')
                    ->whereDate('transaction_date', Carbon::today())
                    ->sum('total_price'),
                'total_transactions' => Transaction::count(),
                'active_promotions' => Promotion::where('is_active', true)
                    ->where('start_date', '<=', Carbon::now())
                    ->where('end_date', '>=', Carbon::now())
                    ->count(),
                'low_stock_count' => ProductDetail::where('stock', '<=', 5)->count(),
                'pending_redemptions' => PointRedemption::where('status', 'pending')->count(),
            ]
        ]);
    }
}