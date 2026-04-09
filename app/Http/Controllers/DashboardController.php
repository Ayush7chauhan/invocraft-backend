<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PersonalContact;
use App\Models\PersonalExpense;
use App\Models\PersonalPurchase;
use App\Models\PersonalTransaction;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = now()->toDateString();

        // ── Business Metrics ─────────────────────────────────────────────────

        // Net ledger balance across all parties
        $transactionBalance = Transaction::where('user_id', $user->id)
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) AS balance')
            ->value('balance') ?? 0;

        $openingBalance = Party::where('user_id', $user->id)->sum('opening_balance');
        $netBalance     = $transactionBalance + $openingBalance;

        $totalReceivable = max(0, $netBalance);
        $totalPayable    = abs(min(0, $netBalance));

        // Today's sales
        $todaySales = Invoice::where('user_id', $user->id)
            ->whereDate('invoice_date', $today)
            ->sum('total_amount');

        // Today's payments received
        $todayPayments = Payment::where('user_id', $user->id)
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Business expenses this month
        $monthExpenses = Expense::where('user_id', $user->id)
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        // Low stock products
        $lowStockProducts = Product::where('user_id', $user->id)
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->where('status', 'active')
            ->select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold')
            ->get();

        // Outstanding invoices (unpaid / partial)
        $outstandingInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->with('party:id,name')
            ->orderByDesc('invoice_date')
            ->limit(5)
            ->get(['id', 'invoice_number', 'party_id', 'invoice_date', 'total_amount', 'paid_amount', 'payment_status']);

        // Recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('party:id,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── Personal Metrics ─────────────────────────────────────────────────

        $todayPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $monthPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        $monthPersonalPurchases = PersonalPurchase::where('user_id', $user->id)
            ->whereYear('purchase_date', now()->year)
            ->whereMonth('purchase_date', now()->month)
            ->sum('amount');

        $totalPersonalGiven    = PersonalTransaction::where('user_id', $user->id)->where('type', 'given')->sum('amount');
        $totalPersonalReceived = PersonalTransaction::where('user_id', $user->id)->where('type', 'received')->sum('amount');

        $personalYouOwe   = 0;
        $personalTheyOwe  = 0;

        PersonalContact::where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->each(function ($contact) use (&$personalYouOwe, &$personalTheyOwe) {
                $balance = $contact->balance;
                $balance < 0
                    ? ($personalYouOwe  += abs($balance))
                    : ($personalTheyOwe += $balance);
            });

        $recentPersonalTransactions = PersonalTransaction::where('user_id', $user->id)
            ->with('personalContact:id,name')
            ->orderByDesc('transaction_date')
            ->limit(5)
            ->get();

        $recentPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->orderByDesc('expense_date')
            ->limit(5)
            ->get();

        return $this->successResponse([
            // Business
            'total_receivable'       => round($totalReceivable, 2),
            'total_payable'          => round($totalPayable, 2),
            'today_sales'            => round($todaySales, 2),
            'today_payments'         => round($todayPayments, 2),
            'month_expenses'         => round($monthExpenses, 2),
            'low_stock_products'     => $lowStockProducts,
            'outstanding_invoices'   => $outstandingInvoices,
            'recent_transactions'    => $recentTransactions,
            // Personal
            'today_personal_expenses'   => round($todayPersonalExpenses, 2),
            'month_personal_expenses'   => round($monthPersonalExpenses, 2),
            'month_personal_purchases'  => round($monthPersonalPurchases, 2),
            'personal_you_owe'          => round($personalYouOwe, 2),
            'personal_they_owe'         => round($personalTheyOwe, 2),
            'total_personal_given'      => round($totalPersonalGiven, 2),
            'total_personal_received'   => round($totalPersonalReceived, 2),
            'recent_personal_transactions' => $recentPersonalTransactions,
            'recent_personal_expenses'     => $recentPersonalExpenses,
        ], 'Dashboard data retrieved successfully');
    }
}
