<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonalContactController;
use App\Http\Controllers\PersonalExpenseController;
use App\Http\Controllers\PersonalPurchaseController;
use App\Http\Controllers\PersonalTransactionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invocraft API Routes
|--------------------------------------------------------------------------
|
| All routes are JSON-only. The custom auth.token middleware validates the
| bearer token and binds the authenticated user onto the request.
|
| Auth flow (DO NOT change):
|   1. POST /send-otp           → generate OTP (last 6 digits of mobile)
|   2. POST /verify-otp         → verify OTP; if new user → requires_registration=true
|   3. POST /update-shop-details → complete shop setup, returns bearer token
|   4. POST /verify-token       → validate an existing bearer token
|
*/

// ── Health check ──────────────────────────────────────────────────────────────
Route::get('/test', fn () => response()->json([
    'success' => true,
    'message' => 'Invocraft API is running.',
]));

// ── Public: Auth ──────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/send-otp',            [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp',          [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp',          [AuthController::class, 'resendOtp']);
    Route::post('/verify-token',        [AuthController::class, 'verifyToken']);
    Route::post('/update-shop-details', [AuthController::class, 'updateShopDetails']);
});

// Legacy (flat) auth routes — kept for backward compatibility with existing frontend
Route::post('/send-otp',            [AuthController::class, 'sendOtp']);
Route::post('/verify-otp',          [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp',          [AuthController::class, 'resendOtp']);
Route::post('/verify-token',        [AuthController::class, 'verifyToken']);
Route::post('/update-shop-details', [AuthController::class, 'updateShopDetails']);

// Dev-only shortcut (never available in production)
if (App::environment('local')) {
    Route::post('/dev/auto-login', [AuthController::class, 'devAutoLogin']);
}

// ── Protected routes (require valid bearer token) ─────────────────────────────
Route::middleware('auth.token')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/daily',   [ReportController::class, 'daily']);
        Route::get('/weekly',  [ReportController::class, 'weekly']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/summary', [ReportController::class, 'summary']);
        Route::get('/range',   [ReportController::class, 'range']);
        Route::get('/chart',   [ReportController::class, 'chart']);
    });

    // ── Business Core ─────────────────────────────────────────────────────────

    // Parties (Customers / Suppliers)
    Route::apiResource('parties', PartyController::class);

    // Categories (for products)
    Route::get('/categories',        [CategoryController::class, 'index']);
    Route::post('/categories',       [CategoryController::class, 'store']);
    Route::delete('/categories/{id}',[CategoryController::class, 'destroy']);

    // Units (pcs, kg, ltr, etc.)
    // seed-defaults must be before apiResource to avoid matching as {unit} show route
    Route::post('/units/seed-defaults', [UnitController::class, 'seedDefaults']);
    Route::apiResource('units', UnitController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Invoices
    Route::get('/invoices',       [InvoiceController::class, 'index']);
    Route::post('/invoices',      [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}',  [InvoiceController::class, 'show']);

    // Payments
    Route::get('/payments',  [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);

    // Business Expenses
    Route::get('/expenses/categories',    [ExpenseController::class, 'categories']);
    Route::apiResource('expenses', ExpenseController::class);

    // Settings (shop billing config)
    Route::get('/settings',  [SettingsController::class, 'show']);
    Route::put('/settings',  [SettingsController::class, 'update']);

    // ── Khata / Transactions ──────────────────────────────────────────────────
    Route::get('/transactions',        [TransactionController::class, 'index']);
    Route::post('/transactions',       [TransactionController::class, 'store']);
    Route::get('/transactions/{id}',   [TransactionController::class, 'show']);
    Route::put('/transactions/{id}',   [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}',[TransactionController::class, 'destroy']);

    // ── Personal Finance ──────────────────────────────────────────────────────

    // Personal Expenses
    Route::apiResource('personal-expenses', PersonalExpenseController::class);

    // Personal Purchases
    Route::apiResource('personal-purchases', PersonalPurchaseController::class);

    // Personal Contacts (Friends, Family, etc.)
    Route::apiResource('personal-contacts', PersonalContactController::class);

    // Personal Transactions (Money given / received)
    Route::get('/personal-transactions',         [PersonalTransactionController::class, 'index']);
    Route::post('/personal-transactions',        [PersonalTransactionController::class, 'store']);
    Route::put('/personal-transactions/{id}',    [PersonalTransactionController::class, 'update']);
    Route::delete('/personal-transactions/{id}', [PersonalTransactionController::class, 'destroy']);
});
