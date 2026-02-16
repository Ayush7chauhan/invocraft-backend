<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PersonalExpenseController;
use App\Http\Controllers\PersonalPurchaseController;
use App\Http\Controllers\PersonalContactController;
use App\Http\Controllers\PersonalTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'status' => 'success'
    ]);
});

// Auth routes (public)
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/update-shop-details', [AuthController::class, 'updateShopDetails']);
Route::post('/verify-token', [AuthController::class, 'verifyToken']);

// Protected routes (require authentication)
Route::middleware('auth.token')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Reports
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::get('/reports/weekly', [ReportController::class, 'weekly']);
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/range', [ReportController::class, 'range']);
    Route::get('/reports/chart', [ReportController::class, 'chart']);

    // Parties (Customers/Suppliers)
    Route::apiResource('parties', PartyController::class);

    // Categories (for products)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Products
    Route::apiResource('products', ProductController::class);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

    // Transactions (Khata)
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // Payments
    Route::post('/payments', [PaymentController::class, 'store']);

    // Personal Expenses
    Route::apiResource('personal-expenses', PersonalExpenseController::class);

    // Personal Purchases
    Route::apiResource('personal-purchases', PersonalPurchaseController::class);

    // Personal Contacts (Friends, Family, etc.)
    Route::apiResource('personal-contacts', PersonalContactController::class);

    // Personal Transactions (Money given/received)
    Route::get('/personal-transactions', [PersonalTransactionController::class, 'index']);
    Route::post('/personal-transactions', [PersonalTransactionController::class, 'store']);
    Route::put('/personal-transactions/{id}', [PersonalTransactionController::class, 'update']);
    Route::delete('/personal-transactions/{id}', [PersonalTransactionController::class, 'destroy']);
});
