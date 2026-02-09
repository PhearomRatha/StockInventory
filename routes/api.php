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
use App\Http\Controllers\AdminController;
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

// Test database connection
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 200,
            'message' => 'Database connected successfully!',
            'database' => config('database.default'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Database connection failed: ' . $e->getMessage(),
        ], 500);
    }
});

// ====================== PUBLIC AUTH ROUTES ======================
// Registration and OTP verification (no auth required)
Route::prefix('auth')->group(function () {
    // Registration
    Route::post('/register', [AuthController::class, 'register']);
    
    // OTP Verification
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/resend-otp', [AuthController::class, 'resendOTP']);
    
    // Check registration status
    Route::post('/check-status', [AuthController::class, 'checkRegistrationStatus']);
    
    // Login
    Route::post('/login', [AuthController::class, 'login']);
});

// Public roles
Route::get('/roles', [RoleController::class, 'publicRoles']);

// ====================== PROTECTED AUTH ROUTES ======================
// Require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Current user info
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    
    // Refresh token
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
    
    // ====================== ADMIN ROUTES ======================
    // User approval workflow (Admin only)
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        
        // User requests
        Route::get('/pending-requests', [AdminController::class, 'getPendingRequests']);
        Route::post('/approve-user', [AdminController::class, 'approveUser']);
        Route::post('/reject-user', [AdminController::class, 'rejectUser']);
        
        // User management
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::post('/toggle-status', [AdminController::class, 'toggleUserStatus']);

    Route::post('/admin/approve-user', [AuthController::class, 'approveUserRequest']);
    Route::post('/admin/reject-user', [AuthController::class, 'rejectUserRequest']);
    });
    
    // ====================== DASHBOARD ======================
    Route::prefix('dashboard')->middleware('role:Admin,Manager,Staff')->group(function () {
        Route::get('/index', [DashboardController::class, 'index']);
    });

    // ====================== ROLES ======================
    Route::middleware('role:Admin')->controller(RoleController::class)->group(function () {
        Route::get('/roles', 'index');
        Route::post('/roles', 'store');
        Route::patch('/roles/{id}', 'update');
        Route::delete('/roles/{id}', 'destroy');
    });

    
    // ====================== PRODUCTS ======================
    Route::middleware('role:Staff')->controller(ProductController::class)->group(function () {
        Route::get('/products', 'index');
        Route::get('/products/{id}', 'show');
        Route::post('/products', 'store');
        Route::get('/products/total', 'totalPro');
        Route::get('/products/stock-status', 'stock');
    });
    
    Route::middleware('role:Admin,Manager')->controller(ProductController::class)->group(function () {
        Route::get('/products', 'index');
        Route::get('/products/{id}', 'show');
        Route::post('/products', 'store');
        Route::patch('/products/{id}', 'update');
        Route::delete('/products/{id}', 'destroy');
        Route::get('/products/total', 'totalPro');
        Route::get('/products/stock-status', 'stock');
    });

    // ====================== CATEGORIES ======================
    Route::middleware('role:Admin,Manager')->controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::get('/categories/{id}', 'show');
        Route::post('/categories', 'store');
        Route::patch('/categories/{id}', 'update');
        Route::delete('/categories/{id}', 'destroy');
    });

    // ====================== SUPPLIERS ======================
    Route::middleware('role:Admin,Manager')->controller(SuppliersController::class)->group(function () {
        Route::get('/suppliers', 'index');
        Route::post('/suppliers', 'store');
        Route::post('/suppliers/{id}', 'update');
        Route::delete('/suppliers/{id}', 'destroy');
    });

    // ====================== CUSTOMERS ======================
    Route::middleware('role:Admin,Manager,Staff')->controller(CustomerController::class)->group(function () {
        Route::get('/customers', 'index');
        Route::get('/customers/{id}', 'show');
        Route::post('/customers', 'store');
        Route::patch('/customers/{id}', 'update');
        Route::delete('/customers/{id}', 'destroy');
    });

    // ====================== STOCK INS ======================
    Route::middleware('role:Admin,Manager,Staff')->controller(StockInsController::class)->group(function () {
        Route::get('/stock-ins', 'index');
        Route::get('stock-ins/overview', 'overview');
        Route::post('/stock-ins', 'store');
        Route::patch('/stock-ins/{id}', 'update');
        Route::delete('/stock-ins/{id}', 'destroy');
        Route::get("/stock-ins/totalStockIn", 'totalStockIn');
    });

    // ====================== STOCK OUTS ======================
    Route::middleware('role:Admin,Manager,Staff')->controller(StockOutsController::class)->group(function () {
        Route::get('/stock-outs', 'index');
        Route::post('/stock-outs', 'store');
        Route::patch('/stock-outs/{id}', 'update');
        Route::get('/stock-outs/{id}', 'destroy');
        Route::get('/stock-out-dashboard', 'dashboardData');
        Route::get('/stock-outs/{id}/receipt', 'receipt');
    });

    // ====================== SALES ======================
    Route::middleware('role:Admin,Manager,Staff')->controller(SalesController::class)->group(function () {
        Route::get('/sales', 'index');
        Route::post('/sales', 'store');
        Route::patch('/sales/{id}', 'update');
        Route::delete('/sales/{id}', 'destroy');
        Route::get('/sales/dashboard', 'dashboard');
        Route::post('/sales/checkout', 'checkoutSale');
        Route::post('/sales/verify-payment', 'verifySalePayment');
        Route::get('/sales/data', 'getSalesData');
    });

    // ====================== PAYMENTS ======================
    Route::middleware('role:Admin,Manager,Staff')->controller(PaymentController::class)->group(function () {
        Route::get('/payments', 'index');
        Route::post('/payments', 'store');
        Route::patch('/payments/{id}', 'update');
        Route::delete('/payments/{id}', 'destroy');
        Route::get('/payments/dashboard', 'dashboard');
        Route::post('/payments/checkout', 'checkoutPayment');
        Route::post('/payments/verify', 'verifyPayment');
    });

    // ====================== REPORTS ======================
    Route::middleware('role:Admin,Manager')->controller(ReportController::class)->group(function () {
        Route::get('/reports/sales', 'salesReport');
        Route::get('/reports/financial', 'financialReport');
        Route::get('/reports/stock', 'stockReport');
        Route::get('/reports/activity-logs', 'activityLogReport');
    });

    // ====================== ACTIVITY LOGS ======================
    Route::middleware('role:Admin')->controller(ActivityLogsController::class)->group(function () {
        Route::get('/activity-logs', 'index');
        Route::get('/activity-logs/filter', 'filter');
        Route::post('/activity-logs', 'store');
        Route::patch('/activity-logs/{id}', 'update');
        Route::delete('/activity-logs/{id}', 'destroy');
    });
});
