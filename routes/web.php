<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\LoyaltyPointsController;
use App\Http\Controllers\Admin\LoyaltyRuleController;
use App\Http\Controllers\Admin\PointRedemptionsController;
use App\Http\Controllers\Admin\PointRewardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionsController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\kasir\KasirController;
use App\Http\Controllers\RFM\RfmController;
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
    // Hapus {id} karena kita pakai Auth::guard('customers')->user()
    Route::get('/profile', [CustomersController::class, 'profile'])->name('customers.profile');
    
    // Samakan as/name agar konsisten dengan view (customers.profile.update)
    Route::post('/profile/update', [CustomersController::class, 'updateProfile'])->name('customers.profile.update');
    
    // Samakan as/name agar konsisten dengan view (customers.password.update)
    Route::put('/profile/password', [CustomersController::class, 'updatePassword'])->name('customers.password.update');

    Route::post('/logout', [LoginController::class, 'logout'])->name('customers.logout');
});

/*
|--------------------------------------------------------------------------
| ADMIN PANEL (Guard: web, Role: admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:admin'])->prefix('admin')->group(function () {
    
    Route::get('/dashboard', function () {
        return view('pages.admin.index');
    })->name('admin.dashboard');

    // User Management
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index')->name('admin.users');
        Route::get('/users/create', 'create')->name('admin.users.create');
        Route::post('/users', 'store')->name('admin.users.store');
        Route::get('/users/{id}', 'show')->name('admin.users.show');
        Route::get('/users/{id}/edit', 'edit')->name('admin.users.edit');
        Route::put('/users/{id}', 'update')->name('admin.users.update');
        Route::delete('/users/{id}', 'destroy')->name('admin.users.destroy');
    });

    // Customer Management
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

    // Product Management
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
        // Product Report specialized
        Route::get('/reports/products', 'productReports')->name('admin.reports.products');
    });

    // Category Management
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index')->name('admin.categories');
        Route::get('/categories/create', 'create')->name('admin.categories.create');
        Route::post('/categories', 'store')->name('admin.categories.store');
        Route::get('/categories/{id}/edit', 'edit')->name('admin.categories.edit');
        Route::put('/categories/{id}', 'update')->name('admin.categories.update');
        Route::delete('/categories/{id}', 'destroy')->name('admin.categories.destroy');
    });

    // Transaction Management
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
        // Promo Check from Transaction Controller
        Route::get('/promotions/check', 'checkPromo')->name('admin.promo.check');
    });

    // Promotions Management
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

    Route::controller(PointRewardController::class)->group(function () {
    // Menampilkan daftar katalog hadiah
    Route::get('/loyalty/rewards', 'index')->name('admin.loyalty.rewards');
    
    // Menampilkan form tambah hadiah
    Route::get('/loyalty/rewards/create', 'create')->name('admin.loyalty.rewards.create');
    
    // Menyimpan hadiah baru
    Route::post('/loyalty/rewards', 'store')->name('admin.loyalty.rewards.store');
    
    // Menampilkan detail hadiah
    Route::get('/loyalty/rewards/{reward}', 'show')->name('admin.loyalty.rewards.show');
    
    // Menampilkan form edit hadiah
    Route::get('/loyalty/rewards/{reward}/edit', 'edit')->name('admin.loyalty.rewards.edit');
    
    // Memperbarui data hadiah
    Route::put('/loyalty/rewards/{reward}', 'update')->name('admin.loyalty.rewards.update');
    
    // Menghapus hadiah
    Route::delete('/loyalty/rewards/{reward}', 'destroy')->name('admin.loyalty.rewards.destroy');
});

    Route::controller(LoyaltyRuleController::class)->group(function (){
        // Halaman Utama Aturan Poin
        Route::get('/loyalty/rule', 'index')->name('admin.loyalty.rule');
        
        // Simpan Aturan Baru
        Route::post('/loyalty/store', 'store')->name('admin.loyalty.rule.store');
        
        // Update Aturan (Gunakan PUT/PATCH karena kita mengupdate data yang sudah ada)
        Route::put('/loyalty/update/{id}', 'update')->name('admin.loyalty.rule.update');
        
        // Hapus Aturan
        Route::delete('/loyalty/delete/{id}', 'destroy')->name('admin.loyalty.rule.destroy');
    });

    Route::controller(PointRedemptionsController::class)
    ->prefix('redemptions')
    ->name('admin.loyalty.redemptions.')
    ->group(function () {
        
        // Halaman Utama: Daftar semua riwayat penukaran
        Route::get('/', 'index')->name('index');
        
        // Form Tambah: Untuk input manual penukaran oleh Admin/Kasir
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        
        // Detail: Melihat detail rincian satu data penukaran
        Route::get('/{id}', 'show')->name('show');
        
        // Update Status: Digunakan untuk memproses klaim (Pending -> Completed/Cancelled)
        Route::put('/{id}', 'update')->name('update');
        
        // Hapus: Menghapus log penukaran
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    Route::controller(LoyaltyPointsController::class)->prefix('points')->name('admin.loyalty.')->group(function () {
        Route::get('/logs', 'index')->name('points.index');
    });

    // Reports Management
    Route::controller(ReportsController::class)->group(function () {
        Route::get('/reports/transactions', 'index')->name('admin.reports.transactions');
        Route::get('/reports/transactions/detail/{id}', 'show')->name('admin.reports.transactions.detail');
        Route::get('/reports/product', [ProductController::class, 'productReports'])->name('admin.product.reports');
    });

    // profil
    Route::get('/myProfile', [UserController::class, 'myProfile'])->name('admin.profile');

    
});

Route::middleware(['auth', 'role:admin,manager'])->prefix('rfm')->name('rfm.')->group(function () {
    Route::get('/',                             [RfmController::class, 'index'])              ->name('index');
    Route::get('/calculate',                    [RfmController::class, 'create'])             ->name('calculate');
    Route::post('/calculate',                   [RfmController::class, 'store'])              ->name('store');
    Route::get('/batch/{batch}',                [RfmController::class, 'showBatch'])          ->name('batch.show');
    Route::patch('/batch/{batch}/labels',       [RfmController::class, 'updateClusterLabels'])->name('batch.labels');
    Route::get('/customer/{customerId}/history',[RfmController::class, 'customerHistory'])    ->name('customer.history');
 
    // API (JSON)
    Route::get('/api/batch/{batch}/scatter',    [RfmController::class, 'scatterData'])        ->name('api.scatter');
    Route::get('/api/elbow',                    [RfmController::class, 'elbowData'])          ->name('elbow');
});

/*
|--------------------------------------------------------------------------
| MANAGER PANEL
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:manager'])->prefix('manager')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.manager.index');
    })->name('manager.dashboard');
});

/*
|--------------------------------------------------------------------------
| KASIR PANEL
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:kasir'])->prefix('kasir')->group(function () {
    Route::get('/dashboard', [KasirController::class, 'index'])->name('kasir.dashboard');
    Route::get('/transactions', [KasirController::class, 'createTransactions'])->name('kasir.create.trasaction');
    Route::post('/transactions/store', [KasirController::class, 'transactionStore'])->name('kasir.store.transaction');
    Route::get('/promotions/check', [TransactionController::class, 'checkPromo'])->name('kasir.promo.check');
});