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
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\DB;



// TEST ROUTES
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

// =========================================================================
// PUBLIC ROUTES WITH RATE LIMITING
// =========================================================================
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// =========================================================================
// GOOGLE OAUTH ROUTES
// =========================================================================
Route::prefix('auth')->group(function () {
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']); // Alias for frontend compatibility
    Route::get('/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);
});

// Public roles listing (for dropdowns, etc.)
Route::get('/roles', [AuthController::class, 'getRoles']);

// Public roles route (no auth required)
Route::get('/roles/public', [RoleController::class, 'publicRoles']);
    

Route::middleware(['auth:sanctum'])->group(function () {

    // Current user info
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

    // Refresh token
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);

    // Change password
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);



    Route::middleware('role:Admin,Manager')->prefix('admin')->group(function () {
        Route::post('/reset-password', [AuthController::class, 'adminResetPassword']);
    });




    Route::middleware('role:Admin,Manager')
        ->controller(UserManagementController::class)
        ->prefix('users')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/toggle-status', 'toggleStatus');
        });


    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        // Admin stats
        Route::get('/stats', [AdminController::class, 'getStats']);

        // Admin user management routes
        Route::get('/pending-requests', [AdminController::class, 'getPendingRequests']);
        Route::post('/approve-user', [AdminController::class, 'approveUser']);
        Route::post('/reject-user', [AdminController::class, 'rejectUser']);
        Route::get('/users/all', [AdminController::class, 'getAllUsers']);
        Route::post('/toggle-user-status', [AdminController::class, 'toggleUserStatus']);
        Route::post('/approve-user-request', [AdminController::class, 'approveUserRequest']);
        Route::post('/reject-user-request', [AdminController::class, 'rejectUserRequest']);
    });


    Route::middleware('role:Admin')
        ->controller(UserController::class)
        ->prefix('legacy-users')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

    Route::prefix('dashboard')
        ->middleware('role:Admin,Manager,Staff')
        ->group(function () {
            Route::get('/index', [DashboardController::class, 'index']);
        });


    Route::middleware('role:Admin')
        ->controller(RoleController::class)
        ->prefix('roles')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });

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


    Route::middleware('role:Admin,Manager')
        ->controller(SuppliersController::class)
        ->prefix('suppliers')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });


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


    Route::middleware('role:Admin,Manager,Staff')
        ->controller(StockOutsController::class)
        ->prefix('stock-outs')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/stock-out-dashboard', 'dashboardData');
            Route::get('/{id}', 'show');
            Route::patch('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::get('/{id}/receipt', 'receipt');
        });


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
            Route::get('/data', 'getSalesData');
            // NOTE: Payment verification now handled via PaymentsController::verifyPayment
            // This maintains consistent API contract using payment_id
        });


 
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
