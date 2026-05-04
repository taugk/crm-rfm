<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\LoyaltyPointsController;
use App\Http\Controllers\Admin\LoyaltyRuleController;
use App\Http\Controllers\Admin\PointRedemptionsController;
use App\Http\Controllers\Admin\PointRewardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionsController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\Kasir\KasirController;
use App\Http\Controllers\Kasir\CustomerController as KasirCustomerController;
use App\Http\Controllers\Kasir\TransactionController as KasirTransactionController;
use App\Http\Controllers\RFM\RfmController;
use App\Models\RfmCalculationBatch;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH (GUEST)
|--------------------------------------------------------------------------
*/
Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/customer', [LoginController::class, 'loginCustomer'])->name('login.customers');
Route::post('/customer/login', [LoginController::class, 'loginCustomerPost'])->name('login.customers.post');
Route::get('/register/customers', [LoginController::class, 'registerCustomer'])->name('register.customers');
Route::post('/register/customers', [LoginController::class, 'registerCustomerPost'])->name('register.customers.post');

Route::post('/otp/send', [LoginController::class, 'sendOtp'])->name('otp.send');
Route::post('/otp/verify', [LoginController::class, 'verifyOtp'])->name('otp.verify');

/*
|--------------------------------------------------------------------------
| CUSTOMER DASHBOARD (Guard: customer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:customers'])->prefix('/customer')->group(function () {
    Route::get('/dashboard', [CustomersController::class, 'index'])->name('customers.dashboard');
    Route::get('/menu', [CustomersController::class, 'menu'])->name('customers.menu');
    Route::get('/transactions', [CustomersController::class, 'transactions'])->name('customers.transactions');
    Route::get('/transactions/detail/{id}', [CustomersController::class, 'transactionShow'])->name('customers.show.transactions');
    Route::get('/redeem-point', [CustomersController::class, 'rewards'])->name('customers.points.redeem');
    Route::post('/redeem-point', [CustomersController::class, 'redeem'])->name('customers.points.redeem.process');
    Route::get('/promo', [CustomersController::class, 'promotions'])->name('customers.promos');

    // --- BAGIAN PROFIL (PERBAIKAN) ---
    Route::get('/profile', [CustomersController::class, 'profile'])->name('customers.profile');
    Route::post('/profile/update', [CustomersController::class, 'updateProfile'])->name('customers.profile.update');
    Route::put('/profile/password', [CustomersController::class, 'updatePassword'])->name('customers.password.update');

    Route::post('/logout', [LoginController::class, 'logout'])->name('customers.logout');
});

/*
|--------------------------------------------------------------------------
| ADMIN PANEL (Guard: web, Role: admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin'])->prefix('admin')->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // User Management (Hanya Admin)
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index')->name('admin.users');
        Route::get('/users/create', 'create')->name('admin.users.create');
        Route::post('/users', 'store')->name('admin.users.store');
        Route::get('/users/{id}', 'show')->name('admin.users.show');
        Route::get('/users/{id}/edit', 'edit')->name('admin.users.edit');
        Route::put('/users/{id}', 'update')->name('admin.users.update');
        Route::delete('/users/{id}', 'destroy')->name('admin.users.destroy');
    });

    // Customer Management (Admin & Manager bisa akses, tapi dengan batasan di controller)
    Route::controller(CustomerController::class)->group(function () {
        Route::get('/customers', 'index')->name('admin.customers');
        Route::get('/customers/create', 'create')->name('admin.customers.create');
        Route::get('/customers/export', 'export')->name('admin.customers.export');
        Route::get('/customers/import', 'import')->name('admin.customers.import');
        Route::post('/customers/import', 'importProcess')->name('admin.customers.import.process');
        Route::get('/customers/{id}', 'show')->name('admin.customers.show');
        Route::post('/customers', 'store')->name('admin.customers.store');
        Route::get('/customers/{id}/edit', 'edit')->name('admin.customers.edit');
        Route::put('/customers/{id}', 'update')->name('admin.customers.update');
        Route::delete('/customers/{id}', 'destroy')->name('admin.customers.destroy');
    });

    // Product Management (Admin & Manager dengan batasan)
    Route::controller(ProductController::class)->group(function () {
        Route::get('/products', 'index')->name('admin.products');
        Route::get('/products/create', 'create')->name('admin.products.create');
        Route::post('/products', 'store')->name('admin.products.store');
        Route::get('/products/export', 'export')->name('admin.products.export');
        Route::get('/products/import', 'import')->name('admin.products.import');
        Route::post('/products/import', 'importProcess')->name('admin.products.import.process');
        Route::get('/products/{id}', 'show')->name('admin.products.show');
        Route::get('/products/{id}/edit', 'edit')->name('admin.products.edit');
        Route::put('/products/{id}', 'update')->name('admin.products.update');
        Route::delete('/products/{id}', 'destroy')->name('admin.products.destroy');
        Route::get('/reports/products', 'productReports')->name('admin.reports.products');
    });

    // Category Management (Admin & Manager dengan batasan)
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index')->name('admin.categories');
        Route::get('/categories/create', 'create')->name('admin.categories.create');
        Route::post('/categories', 'store')->name('admin.categories.store');
        Route::get('/categories/{id}/edit', 'edit')->name('admin.categories.edit');
        Route::put('/categories/{id}', 'update')->name('admin.categories.update');
        Route::delete('/categories/{id}', 'destroy')->name('admin.categories.destroy');
    });

    // Transaction Management (Admin & Manager)
    Route::controller(TransactionController::class)->group(function () {
        Route::get('/transactions', 'index')->name('admin.transactions');
        Route::get('/transactions/create', 'create')->name('admin.transactions.create');
        Route::get('/transactions/export', 'export')->name('admin.transactions.export');
        Route::get('/transactions/import', 'import')->name('admin.transactions.import');
        Route::post('/transactions/import', 'importProcess')->name('admin.transactions.import.process');
        Route::get('/transactions/{id}', 'show')->name('admin.transactions.show');
        Route::post('/transactions', 'store')->name('admin.transactions.store');
        Route::get('/transactions/{id}/edit', 'edit')->name('admin.transactions.edit');
        Route::put('/transactions/{id}', 'update')->name('admin.transactions.update');
        Route::delete('/transactions/{id}', 'destroy')->name('admin.transactions.destroy');
        Route::get('/promotions/check', 'checkPromo')->name('admin.promo.check');
    });

    // Promotions Management (Admin & Manager dengan batasan)
    Route::controller(PromotionsController::class)->group(function () {
        Route::get('/promotions', 'index')->name('admin.promo');
        Route::get('/promotions/create', 'create')->name('admin.promo.create');
        Route::post('/promotions/store', 'store')->name('admin.promo.store');
        Route::get('/promotions/{id}/show', 'show')->name('admin.promo.show');
        Route::get('/promotions/{id}/edit', 'edit')->name('admin.promo.edit');
        Route::put('/promotions/{id}/update', 'update')->name('admin.promo.update');
        Route::delete('/promotions/{id}/destroy', 'destroy')->name('admin.promo.destroy');
        Route::get('/promotions/category', 'category')->name('admin.promo.category');
        Route::get('/promotions/export', 'export')->name('admin.promo.export');
        Route::post('/promotions/import', 'import')->name('admin.promo.import');
    });

    // Point Rewards Management (Admin & Manager - dengan batasan di controller)
    Route::controller(PointRewardController::class)->group(function () {
        Route::get('/loyalty/rewards', 'index')->name('admin.loyalty.rewards');
        Route::get('/loyalty/rewards/create', 'create')->name('admin.loyalty.rewards.create');
        Route::post('/loyalty/rewards', 'store')->name('admin.loyalty.rewards.store');
        Route::get('/loyalty/rewards/{reward}', 'show')->name('admin.loyalty.rewards.show');
        Route::get('/loyalty/rewards/{reward}/edit', 'edit')->name('admin.loyalty.rewards.edit');
        Route::put('/loyalty/rewards/{reward}', 'update')->name('admin.loyalty.rewards.update');
        Route::delete('/loyalty/rewards/{reward}', 'destroy')->name('admin.loyalty.rewards.destroy');
    });

    // Loyalty Rules Management (Admin & Manager - dengan batasan di controller)
    Route::controller(LoyaltyRuleController::class)->group(function (){
        Route::get('/loyalty/rule', 'index')->name('admin.loyalty.rule');
        Route::post('/loyalty/store', 'store')->name('admin.loyalty.rule.store');
        Route::put('/loyalty/update/{id}', 'update')->name('admin.loyalty.rule.update');
        Route::delete('/loyalty/delete/{id}', 'destroy')->name('admin.loyalty.rule.destroy');
        
        // Route tambahan untuk manajemen aturan
        Route::post('/loyalty/rule/{id}/activate', 'activate')->name('admin.loyalty.rule.activate');
        Route::post('/loyalty/rule/{id}/deactivate', 'deactivate')->name('admin.loyalty.rule.deactivate');
        Route::post('/loyalty/rule/{id}/duplicate', 'duplicate')->name('admin.loyalty.rule.duplicate');
    });

    // Point Redemptions Management (Admin & Manager - dengan batasan di controller)
    Route::controller(PointRedemptionsController::class)
        ->prefix('redemptions')
        ->name('admin.loyalty.redemptions.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{pointRedemption}', 'show')->name('show');
            Route::get('/{pointRedemption}/edit', 'edit')->name('edit');
            Route::put('/{pointRedemption}', 'update')->name('update');
            Route::delete('/{pointRedemption}', 'destroy')->name('destroy');
            
            // Route tambahan untuk approval process
            Route::post('/{pointRedemption}/approve', 'approve')->name('approve');
            Route::post('/{pointRedemption}/complete', 'complete')->name('complete');
            Route::post('/{pointRedemption}/cancel', 'cancel')->name('cancel');
        });

    // Loyalty Points Logs (Admin & Manager - dengan batasan di controller)
    Route::controller(LoyaltyPointsController::class)
        ->prefix('points')
        ->name('admin.loyalty.')
        ->group(function () {
            Route::get('/logs', 'index')->name('points.index');
            Route::get('/logs/create', 'create')->name('points.create');
            Route::post('/logs', 'store')->name('points.store');
            Route::get('/logs/{loyaltyPoints}', 'show')->name('points.show');
            Route::get('/logs/{loyaltyPoints}/edit', 'edit')->name('points.edit');
            Route::put('/logs/{loyaltyPoints}', 'update')->name('points.update');
            Route::delete('/logs/{loyaltyPoints}', 'destroy')->name('points.destroy');
            
            // Route tambahan
            Route::post('/logs/bulk-delete', 'bulkDelete')->name('points.bulk-delete');
            Route::get('/logs/export', 'export')->name('points.export');
            Route::get('/logs/customer/{customerId}/summary', 'customerSummary')->name('points.customer-summary');
        });

    // Reports Management (Admin & Manager)
    Route::controller(ReportsController::class)->group(function () {
        Route::get('/reports/transactions', 'index')->name('admin.reports.transactions');
        Route::get('/reports/transactions/detail/{id}', 'show')->name('admin.reports.transactions.detail');
        Route::get('/reports/product', [ProductController::class, 'productReports'])->name('admin.product.reports');
    });

    // Profile
    Route::get('/myProfile', [UserController::class, 'myProfile'])->name('admin.profile.index');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('admin.profile.update');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('admin.profile.password');
});

/*
|--------------------------------------------------------------------------
| MANAGER PANEL (Guard: web, Role: manager)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:manager'])->prefix('manager')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('manager.dashboard');
    
    // Manager bisa mengakses beberapa fitur dengan prefix manager
    // Customer Management (Read-only untuk manager)
    Route::controller(CustomerController::class)->prefix('customers')->group(function () {
        Route::get('/', 'index')->name('manager.customers');
        Route::get('/{id}', 'show')->name('manager.customers.show');
        Route::get('/{id}/edit', 'edit')->name('manager.customers.edit');
        Route::put('/{id}', 'update')->name('manager.customers.update');
        // Manager tidak bisa create/delete customer
    });
    
    // Product Management (Read-only untuk manager)
    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::get('/', 'index')->name('manager.products');
        Route::get('/{id}', 'show')->name('manager.products.show');
        Route::get('/{id}/edit', 'edit')->name('manager.products.edit');
        Route::put('/{id}', 'update')->name('manager.products.update');
        // Manager tidak bisa create/delete product
    });
    
    // Transaction Management (Manager bisa melihat dan update status)
    Route::controller(TransactionController::class)->prefix('transactions')->group(function () {
        Route::get('/', 'index')->name('manager.transactions');
        Route::get('/{id}', 'show')->name('manager.transactions.show');
        Route::get('/{id}/edit', 'edit')->name('manager.transactions.edit');
        Route::put('/{id}', 'update')->name('manager.transactions.update');
    });
    Route::controller(PromotionsController::class)->group(function () {
        Route::get('/promotions', 'index')->name('manager.promo');
        Route::get('/promotions/create', 'create')->name('manager.promo.create');
        Route::post('/promotions/store', 'store')->name('manager.promo.store');
        Route::get('/promotions/{id}/show', 'show')->name('manager.promo.show');
        Route::get('/promotions/{id}/edit', 'edit')->name('manager.promo.edit');
        Route::put('/promotions/{id}/update', 'update')->name('manager.promo.update');
        Route::delete('/promotions/{id}/destroy', 'destroy')->name('manager.promo.destroy');
        Route::get('/promotions/category', 'category')->name('manager.promo.category');
        Route::get('/promotions/export', 'export')->name('manager.promo.export');
        Route::post('/promotions/import', 'import')->name('manager.promo.import');
    });
    
    // Loyalty Rewards (Manager terbatas)
    Route::controller(PointRewardController::class)->prefix('loyalty/rewards')->group(function () {
        Route::get('/', 'index')->name('manager.loyalty.rewards');
         Route::get('/create', 'create')->name('manager.loyalty.rewards.create');  // Tambahkan ini
        Route::post('/', 'store')->name('manager.loyalty.rewards.store');    
        Route::get('/{reward}', 'show')->name('manager.loyalty.rewards.show');
        Route::get('/{reward}/edit', 'edit')->name('manager.loyalty.rewards.edit');
        Route::put('/{reward}', 'update')->name('manager.loyalty.rewards.update');
        Route::delete('/{reward}', 'destroy')->name('manager.loyalty.rewards.destroy');
        // Manager tidak bisa create/delete reward

        
    });
    
    // Loyalty Rules (Manager terbatas)
    Route::controller(LoyaltyRuleController::class)->prefix('loyalty/rules')->group(function () {
        Route::get('/', 'index')->name('manager.loyalty.rules');
        Route::post('/store', 'store')->name('manager.loyalty.rule.store');
        Route::put('/{id}', 'update')->name('manager.loyalty.rules.update');
        Route::post('/{id}/activate', 'activate')->name('manager.loyalty.rules.activate');
        Route::post('/{id}/deactivate', 'deactivate')->name('manager.loyalty.rules.deactivate');
    });
    
    // Redemptions (Manager bisa memproses)
    Route::controller(PointRedemptionsController::class)
        ->prefix('redemptions')
        ->name('manager.loyalty.redemptions.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{pointRedemption}', 'show')->name('show');
            Route::get('/{pointRedemption}/edit', 'edit')->name('edit');
            Route::put('/{pointRedemption}', 'update')->name('update');
            Route::post('/{pointRedemption}/approve', 'approve')->name('approve');
            Route::post('/{pointRedemption}/complete', 'complete')->name('complete');
            Route::post('/{pointRedemption}/cancel', 'cancel')->name('cancel');
        });
    
    // Points Logs (Manager bisa melihat dan melakukan adjustment kecil)
    Route::controller(LoyaltyPointsController::class)
        ->prefix('points')
        ->name('manager.loyalty.')
        ->group(function () {
            Route::get('/logs', 'index')->name('points.index');
            Route::get('/logs/create', 'create')->name('points.create');
            Route::post('/logs', 'store')->name('points.store');
            Route::get('/logs/{loyaltyPoints}', 'show')->name('points.show');
            Route::get('/logs/customer/{customerId}/summary', 'customerSummary')->name('points.customer-summary');
        });
    
    // Reports (Manager bisa melihat reports)
    Route::controller(ReportsController::class)->prefix('reports')->group(function () {
        Route::get('/transactions', 'index')->name('manager.reports.transactions');
        Route::get('/transactions/detail/{id}', 'show')->name('manager.reports.transactions.detail');
        Route::get('/product', [ProductController::class, 'productReports'])->name('manager.product.reports');
    });

    Route::prefix('profile')->name('manager.profile.')->controller(UserController::class)->group(function () {
        Route::get('/', 'myProfile')->name('index');
        Route::put('/update', 'updateProfile')->name('update');
        Route::put('/password', 'updatePassword')->name('password');
    });
});

/*
|--------------------------------------------------------------------------
| KASIR PANEL (Guard: web, role: kasir)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [KasirController::class, 'index'])->name('dashboard');
    Route::get('/', [KasirController::class, 'index'])->name('home');
    
    // POS & Transaksi
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [KasirTransactionController::class, 'index'])->name('index');
        Route::post('/store', [KasirTransactionController::class, 'store'])->name('store');
        Route::post('/check-promo', [KasirController::class, 'checkPromo'])->name('check-promo');
    });
    
    // Transaksi
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/create', [KasirController::class, 'createTransaction'])->name('create');
        Route::get('/history', [KasirController::class, 'transactionHistory'])->name('history');
        Route::get('/{id}', [KasirController::class, 'showTransaction'])->name('show');
        Route::get('/{id}/print', [KasirController::class, 'printInvoice'])->name('print');
    });
    
    // Promotions
    Route::get('/promotions', [KasirController::class, 'promotions'])->name('promo.index');
    
    // Customers / Members
    Route::prefix('customers')->name('members.')->group(function () {
        Route::get('/', [KasirCustomerController::class, 'index'])->name('index');
        Route::get('/create', [KasirCustomerController::class, 'create'])->name('create');
        Route::post('/store', [KasirCustomerController::class, 'store'])->name('store');
        Route::get('/check', [KasirCustomerController::class, 'check'])->name('check');
        Route::get('/search', [KasirCustomerController::class, 'search'])->name('search');
        Route::get('/{id}', [KasirCustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [KasirCustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [KasirCustomerController::class, 'update'])->name('update');
    });
    
    // Redeem Points
    Route::prefix('redeem')->name('redeem.')->group(function () {
        Route::get('/', [RedeemController::class, 'index'])->name('index');
        Route::post('/points', [RedeemController::class, 'redeemPoints'])->name('points');
        Route::post('/check-points', [RedeemController::class, 'checkPoints'])->name('check-points');
        Route::get('/history', [RedeemController::class, 'history'])->name('history');
    });
    
    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });
    
    // Alias for menu
    Route::get('/menu', function () {
        return redirect()->route('kasir.pos.index');
    })->name('menu');
});
/*
|--------------------------------------------------------------------------
| RFM ANALYTICS (Admin & Manager - Full Access)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin,manager'])->prefix('rfm')->name('rfm.')->group(function () {
    
    // Pages
    Route::get('/', [RfmController::class, 'showDashboard'])->name('index');
    Route::get('/about', [RfmController::class, 'showAbout'])->name('about');
    Route::get('/calculate', [RfmController::class, 'showCalculateForm'])->name('calculate');
    Route::get('/batch/{batchId}', [RfmController::class, 'showBatchDetail'])->name('batch.detail');
    Route::get('/customer/{customerId}/history', [RfmController::class, 'showCustomerHistory'])->name('customer.history');

    // API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/calculate', [RfmController::class, 'calculate'])->name('calculate');
        Route::get('/dashboard', [RfmController::class, 'index'])->name('dashboard');
        Route::get('/elbow', [RfmController::class, 'elbow'])->name('elbow');
        Route::get('/dbi-comparison', [RfmController::class, 'dbiComparison'])->name('dbi_compare');
        Route::get('/batches', [RfmController::class, 'batches'])->name('batches');
        Route::get('/batches/{batch}', [RfmController::class, 'batchDetail'])->name('batch_detail');
        Route::get('/batches/{batch}/raw', [RfmController::class, 'rawData'])->name('raw');
        Route::get('/batches/{batch}/normalized', [RfmController::class, 'normalizedData'])->name('normalized');
        Route::get('/batches/{batch}/iterations', [RfmController::class, 'iterations'])->name('iterations');
        Route::get('/batches/{batch}/centroids', [RfmController::class, 'centroids'])->name('centroids');
        Route::get('/batches/{batch}/assignments', [RfmController::class, 'assignments'])->name('assignments');
        Route::get('/batches/{batch}/scores', [RfmController::class, 'scores'])->name('scores');
        Route::get('/batches/{batch}/scatter', [RfmController::class, 'scatterData'])->name('scatter');
        Route::get('/batches/{batch}/dbi', [RfmController::class, 'dbi'])->name('dbi_detail');
        Route::get('/customers/{customer}/history', [RfmController::class, 'customerHistory'])->name('customer_history');
        Route::get('/batches/{batch}/segment-history', [RfmController::class, 'segmentHistory'])->name('segment_history');
    });
});
