<?php


use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Customers\CustomersController;
use Illuminate\Support\Facades\Route;

// ================= AUTH (GUEST) =================
Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/customer', [LoginController::class, 'loginCustomer'])->name('login.customers');
Route::post('/customer/login', [LoginController::class, 'loginCustomerPost'])->name('login.customers.post');
Route::get('/register/customers', [LoginController::class, 'registerCustomer'])->name('register.customers');
Route::post('/register/customers', [LoginController::class, 'registerCustomerPost'])->name('register.customers.post');
Route::post('/otp/send', [LoginController::class, 'sendOtp'])->name('otp.send');

Route::post('/otp/verify', [LoginController::class, 'verifyOtp'])->name('otp.verify');

// ================= CUSTOMER DASHBOARD (Guard: customer) =================
Route::middleware(['auth:customers'])->group(function () {
    Route::get('/customers/dashboard', [CustomersController::class, 'index'])->name('customers.dashboard');
    Route::get('/customers/transactions', [CustomersController::class, 'transactions'])->name('customers.transactions');
});

// ================= ADMIN (Format Manual Kembali) =================
Route::middleware(['auth:web', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('pages.admin.index');
    })->name('admin.dashboard');

    // User Management
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{id}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

    // Customer Management
    Route::get('/admin/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('admin.customers');
    Route::get('/admin/customers/create', [\App\Http\Controllers\Admin\CustomerController::class, 'create'])->name('admin.customers.create');
    Route::get('/admin/customers/export', [\App\Http\Controllers\Admin\CustomerController::class, 'export'])->name('admin.customers.export');
    Route::get('/admin/customers/import', [\App\Http\Controllers\Admin\CustomerController::class, 'import'])->name('admin.customers.import');
    Route::post('/admin/customers/import', [\App\Http\Controllers\Admin\CustomerController::class, 'importProcess'])->name('admin.customers.import.process');
    Route::get('/admin/customers/{id}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('admin.customers.show');
    Route::post('/admin/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('admin.customers.store');
    Route::get('/admin/customers/{id}/edit', [\App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('admin.customers.edit');
    Route::put('/admin/customers/{id}', [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('admin.customers.update');
    Route::delete('/admin/customers/{id}', [\App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('admin.customers.destroy');

    // Product Management
    Route::get('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products');
    Route::get('/admin/products/create', [\App\Http\Controllers\Admin\ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/export', [\App\Http\Controllers\Admin\ProductController::class, 'export'])->name('admin.products.export');
    Route::get('/admin/products/import', [\App\Http\Controllers\Admin\ProductController::class, 'import'])->name('admin.products.import');
    Route::post('/admin/products/import', [\App\Http\Controllers\Admin\ProductController::class, 'importProcess'])->name('admin.products.import.process');
    Route::get('/admin/products/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'show'])->name('admin.products.show');
    Route::get('/admin/products/{id}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/products/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');

    // Category Management
    Route::get('/admin/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::get('/admin/categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categories/{id}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/admin/categories/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Transaction Management
    Route::get('/admin/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('admin.transactions');
    Route::get('/admin/transactions/create', [\App\Http\Controllers\Admin\TransactionController::class, 'create'])->name('admin.transactions.create');
    Route::get('/admin/transactions/export', [\App\Http\Controllers\Admin\TransactionController::class, 'export'])->name('admin.transactions.export');
    Route::get('/admin/transactions/import', [\App\Http\Controllers\Admin\TransactionController::class, 'import'])->name('admin.transactions.import');
    Route::post('/admin/transactions/import', [\App\Http\Controllers\Admin\TransactionController::class, 'importProcess'])->name('admin.transactions.import.process');
    Route::get('/admin/transactions/{id}', [\App\Http\Controllers\Admin\TransactionController::class, 'show'])->name('admin.transactions.show');
    Route::post('/admin/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'store'])->name('admin.transactions.store');
    Route::get('/admin/transactions/{id}/edit', [\App\Http\Controllers\Admin\TransactionController::class, 'edit'])->name('admin.transactions.edit');
    Route::put('/admin/transactions/{id}', [\App\Http\Controllers\Admin\TransactionController::class, 'update'])->name('admin.transactions.update');
    Route::delete('/admin/transactions/{id}', [\App\Http\Controllers\Admin\TransactionController::class, 'destroy'])->name('admin.transactions.destroy');
    

    Route::prefix('admin')->group(function () {
    // Tampilan Utama & Filter
    Route::get('/promotions', [\App\Http\Controllers\Admin\PromotionsController::class, 'index'])->name('admin.promo');
    
    // Create & Store
    Route::get('/promotions/create', [\App\Http\Controllers\Admin\PromotionsController::class, 'create'])->name('admin.promo.create');
    Route::post('/promotions/store', [\App\Http\Controllers\Admin\PromotionsController::class, 'store'])->name('admin.promo.store');
    Route::get('/promotions/check', [TransactionController::class, 'checkPromo'])->name('admin.promo.check');
    
    // Show, Edit & Update
    Route::get('/promotions/{id}/show', [\App\Http\Controllers\Admin\PromotionsController::class, 'show'])->name('admin.promo.show');
    Route::get('/promotions/{id}/edit', [\App\Http\Controllers\Admin\PromotionsController::class, 'edit'])->name('admin.promo.edit');
    Route::put('/promotions/{id}/update', [\App\Http\Controllers\Admin\PromotionsController::class, 'update'])->name('admin.promo.update');
    
    // Delete
    Route::delete('/promotions/{id}/destroy', [\App\Http\Controllers\Admin\PromotionsController::class, 'destroy'])->name('admin.promo.destroy');

    // Data Management (Import, Export, Category)
    Route::get('/promotions/category', [\App\Http\Controllers\Admin\PromotionsController::class, 'category'])->name('admin.promo.category');
    Route::get('/promotions/export', [\App\Http\Controllers\Admin\PromotionsController::class, 'export'])->name('admin.promo.export');
    Route::post('/promotions/import', [\App\Http\Controllers\Admin\PromotionsController::class, 'import'])->name('admin.promo.import');
});

    // Reports
    Route::get('/admin/reports/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'transactionReports'])->name('admin.reports.transactions');
    Route::get('/admin/reports/products', [\App\Http\Controllers\Admin\ProductController::class, 'productReports'])->name('admin.reports.products');
    
});

// ================= MANAGER =================
Route::middleware(['auth:web', 'role:manager'])->group(function () {
    Route::get('/manager/dashboard', function () {
        return view('pages.manager.index');
    })->name('manager.dashboard');
});

// ================= KASIR =================
Route::middleware(['auth:web', 'role:kasir'])->group(function () {
    Route::get('/kasir/dashboard', function () {
        return view('pages.kasir.index');
    })->name('kasir.dashboard');
});