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
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| API Routes - Stock Inventory System
|--------------------------------------------------------------------------
|
| All API endpoints organized by resource type
| Role-based access control with Sanctum authentication
|
*/

// ============================================================================
// TEST ROUTES
// ============================================================================

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status'  => 200,
            'message' => 'Database connected successfully!',
            'database' => config('database.default'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 500,
            'message' => 'Database connection failed: ' . $e->getMessage(),
        ], 500);
    }
});


// ============================================================================
// PUBLIC AUTH ROUTES (No Authentication Required)
// ============================================================================

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

// Public roles listing
Route::get('/roles', [RoleController::class, 'publicRoles']);


// ============================================================================
// PROTECTED AUTH ROUTES (Authentication Required)
// ============================================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // Current user info
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

    // Refresh token
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);


    // =========================================================================
    // ADMIN ROUTES (Admin Role Only)
    // =========================================================================

    Route::middleware('role:Admin')->prefix('admin')->group(function () {

        // User requests management
        Route::get('/pending-requests', [AdminController::class, 'getPendingRequests']);
        Route::post('/approve-user', [AdminController::class, 'approveUser']);
        Route::post('/reject-user', [AdminController::class, 'rejectUser']);

        // User management
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::get('/stats', [AdminController::class, 'getStats']);
        Route::post('/toggle-status', [AdminController::class, 'toggleUserStatus']);

        // Alternative routes (seems duplicated)
        Route::post('/approve-user-request', [AuthController::class, 'approveUserRequest']);
        Route::post('/reject-user-request', [AuthController::class, 'rejectUserRequest']);
    });


    // =========================================================================
    // DASHBOARD ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::prefix('dashboard')
        ->middleware('role:Admin,Manager,Staff')
        ->group(function () {
            Route::get('/index', [DashboardController::class, 'index']);
        });


    // =========================================================================
    // ROLES ROUTES (Admin Only)
    // =========================================================================

    Route::middleware('role:Admin')
        ->controller(RoleController::class)
        ->prefix('roles')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });


    // =========================================================================
    // PRODUCTS ROUTES
    // =========================================================================

    // Staff can only view products
    Route::middleware('role:Staff')
        ->controller(ProductController::class)
        ->prefix('products')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::get('/total', 'totalPro');
            Route::get('/stock-status', 'stock');
        });

    // Admin and Manager have full access
    Route::middleware('role:Admin,Manager')
        ->controller(ProductController::class)
        ->prefix('products')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/total', 'totalPro');
            Route::get('/stock-status', 'stock');
        });


    // =========================================================================
    // CATEGORIES ROUTES (Admin, Manager Only)
    // =========================================================================

    Route::middleware('role:Admin,Manager')
        ->controller(CategoryController::class)
        ->prefix('categories')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });


    // =========================================================================
    // SUPPLIERS ROUTES (Admin, Manager Only)
    // =========================================================================

    Route::middleware('role:Admin,Manager')
        ->controller(SuppliersController::class)
        ->prefix('suppliers')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });


    // =========================================================================
    // CUSTOMERS ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::middleware('role:Admin,Manager,Staff')
        ->controller(CustomerController::class)
        ->prefix('customers')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });


    // =========================================================================
    // STOCK INS ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::middleware('role:Admin,Manager,Staff')
        ->controller(StockInsController::class)
        ->prefix('stock-ins')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/overview', 'overview');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/totalStockIn', 'totalStockIn');
        });


    // =========================================================================
    // STOCK OUTS ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::middleware('role:Admin,Manager,Staff')
        ->controller(StockOutsController::class)
        ->prefix('stock-outs')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::get('/{id}', 'show');  // Note: was 'destroy' in original, likely typo
            Route::get('/stock-out-dashboard', 'dashboardData');
            Route::get('/{id}/receipt', 'receipt');
        });


    // =========================================================================
    // SALES ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::middleware('role:Admin,Manager,Staff')
        ->controller(SalesController::class)
        ->prefix('sales')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/dashboard', 'dashboard');
            Route::post('/checkout', 'checkoutSale');
            Route::post('/verify-payment', 'verifySalePayment');
            Route::get('/data', 'getSalesData');
        });


    // =========================================================================
    // PAYMENTS ROUTES (Admin, Manager, Staff)
    // =========================================================================

    Route::middleware('role:Admin,Manager,Staff')
        ->controller(PaymentController::class)
        ->prefix('payments')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/dashboard', 'dashboard');
            Route::post('/checkout', 'checkoutPayment');
            Route::post('/verify', 'verifyPayment');
        });


    // =========================================================================
    // REPORTS ROUTES (Admin, Manager Only)
    // =========================================================================

    Route::middleware('role:Admin,Manager')
        ->controller(ReportController::class)
        ->prefix('reports')
        ->group(function () {
            Route::get('/sales', 'salesReport');
            Route::get('/financial', 'financialReport');
            Route::get('/stock', 'stockReport');
            Route::get('/activity-logs', 'activityLogReport');
        });


    // =========================================================================
    // ACTIVITY LOGS ROUTES (Admin Only)
    // =========================================================================

    Route::middleware('role:Admin')
        ->controller(ActivityLogsController::class)
        ->prefix('activity-logs')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/filter', 'filter');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

});
