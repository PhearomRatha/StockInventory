<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\StockInsController;
use App\Http\Controllers\StockOutsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All API endpoints for Stock Inventory System
| Role-based access and token protection included
|--------------------------------------------------------------------------
*/
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return "Database connected successfully!";
    } catch (\Exception $e) {
        return "Database connection failed: " . $e->getMessage();
    }
});

// ---------------------- AUTH ----------------------
// Login route (public)
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pubroles', [RoleController::class, 'publicRoles']);
Route::post('/signup', [AuthController::class, 'register']);
// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
    // Users (Admin only)
    Route::middleware('role:Admin')->controller(UserController::class)->group(function () {
        Route::get('/users', 'index');
         Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', 'update');
        Route::delete('/users/{id}', 'destroy');
    });

});

// ---------------------- PROTECTED ROUTES ----------------------
Route::middleware(['auth:sanctum'])->group(function () {

    // ---------------------- DASHBOARD ----------------------
Route::prefix('dashboard')->group(function () {
    // Route::get('/total-customers', [DashboardController::class, 'totalCustomer'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/total-products', [DashboardController::class, 'totalProduct'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/total-suppliers', [DashboardController::class, 'totalSupplier'])->middleware('role:Admin,Manager');
    // Route::get('/total-sales', [DashboardController::class, 'totalSales'])->middleware('role:Admin,Manager');
    // Route::get('/total-stockin', [DashboardController::class, 'totalStockIn'])->middleware('role:Admin,Manager,Staff');
    Route::get('/index', [DashboardController::class, 'index'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/stockin-summary', [DashboardController::class, 'stockInSummary'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/stock-alert', [DashboardController::class, 'stockAlert'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/sales-trend', [DashboardController::class, 'salesTrend'])->middleware('role:Admin,Manager,Staff');
    // Route::get('/stock-levels', [DashboardController::class, 'stockLevels'])->middleware('role:Admin,Manager,Staff');
});

    // ---------------------- ROLES ----------------------
    Route::middleware('role:Admin')->controller(RoleController::class)->group(function () {
        Route::get('/roles', 'index');
        Route::post('/roles', 'store');
        Route::patch('/roles/{id}', 'update');
        Route::delete('/roles/{id}', 'destroy');
    });

    // ---------------------- PRODUCTS ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(ProductController::class)->group(function () {
        Route::get('/products', 'index');
        Route::get('/products/{id}', 'show');
        Route::post('/products', 'store');
        Route::patch('/products/{id}', 'update');
        Route::delete('/products/{id}', 'destroy');
        Route::get('/products/total', 'totalPro');
        Route::get('/products/stock-status', 'stock');
    });

    // ---------------------- CATEGORIES ----------------------
    Route::middleware('role:Admin,Manager')->controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::get('/categories/{id}', 'show');
        Route::post('/categories', 'store');
        Route::patch('/categories/{id}', 'update');
        Route::delete('/categories/{id}', 'destroy');
    });

    // ---------------------- SUPPLIERS ----------------------
    Route::middleware('role:Admin,Manager')->controller(SuppliersController::class)->group(function () {
        Route::get('/suppliers', 'index');
        Route::post('/suppliers', 'store');
        Route::post('/suppliers/{id}', 'update');
        Route::delete('/suppliers/{id}', 'destroy');
    });

    // ---------------------- CUSTOMERS ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(CustomerController::class)->group(function () {
        Route::get('/customers', 'index');
        Route::get('/customers/{id}', 'show');
        Route::post('/customers', 'store');
        Route::patch('/customers/{id}', 'update');
        Route::delete('/customers/{id}', 'destroy');
    });

    // ---------------------- STOCK INS ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(StockInsController::class)->group(function () {
        Route::get('/stock-ins', 'index');
        Route::get('stock-ins/overview', 'overview');
        Route::post('/stock-ins', 'store');
        Route::patch('/stock-ins/{id}', 'update');
        Route::delete('/stock-ins/{id}', 'destroy');
        Route::get("/stock-ins/totalStockIn", 'totalStockIn');
    });

    // ---------------------- STOCK OUTS ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(StockOutsController::class)->group(function () {
        Route::get('/stock-outs', 'index');
        Route::post('/stock-outs', 'store');
        Route::patch('/stock-outs/{id}', 'update');
        Route::get('/stock-outs/{id}', 'destroy');
        Route::get('/stock-out-dashboard', 'dashboardData');

        Route::get('/stock-outs/{id}/receipt', 'receipt');
    });

    // ---------------------- SALES ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(SalesController::class)->group(function () {
        Route::get('/sales', 'index');
        Route::post('/sales', 'store');
        Route::patch('/sales/{id}', 'update');
        Route::delete('/sales/{id}', 'destroy');
        Route::get('/sales/dashboard', 'dashboard');
        Route::post('/sales/checkout', 'checkoutSale');
        Route::post('/sales/verify-payment', 'verifySalePayment');
    });

    // ---------------------- PAYMENTS ----------------------
    Route::middleware('role:Admin,Manager,Staff')->controller(PaymentController::class)->group(function () {
        Route::get('/payments', 'index');
        Route::post('/payments', 'store');
        Route::patch('/payments/{id}', 'update');
        Route::delete('/payments/{id}', 'destroy');
        Route::get('/payments/dashboard', 'dashboard');
        Route::post('/payments/checkout', 'checkoutPayment');
        Route::post('/payments/verify', 'verifyPayment');
    });

    // ---------------------- REPORTS ----------------------
    Route::middleware('role:Admin,Manager')->controller(ReportController::class)->group(function () {
        Route::get('/reports/sales', 'salesReport');
        Route::get('/reports/financial', 'financialReport');
        Route::get('/reports/stock', 'stockReport');
        Route::get('/reports/activity-logs', 'activityLogReport');
    });


   // ---------------------- ACTIVITY LOGS ----------------------
Route::middleware('role:Admin')->controller(ActivityLogsController::class)->group(function () {
    Route::get('/activity-logs', 'index');
    Route::get('/activity-logs/filter', 'filter');   // <-- ADD THIS
    Route::post('/activity-logs', 'store');
    Route::patch('/activity-logs/{id}', 'update');
    Route::delete('/activity-logs/{id}', 'destroy');
});


});
