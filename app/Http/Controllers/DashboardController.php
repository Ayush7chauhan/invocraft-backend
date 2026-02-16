<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\PersonalExpense;
use App\Models\PersonalPurchase;
use App\Models\PersonalContact;
use App\Models\PersonalTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');
        
        // BUSINESS METRICS
        // Calculate from transactions
        $transactionBalance = Transaction::where('user_id', $user->id)
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;
        
        // Calculate from parties opening balances
        // Positive opening balance = customer owes you (receivable)
        // Negative opening balance = you owe supplier (payable)
        $partiesOpeningBalance = Party::where('user_id', $user->id)
            ->selectRaw('SUM(opening_balance) as total')
            ->value('total') ?? 0;
        
        // Combine transaction balance with opening balances
        $totalBusinessBalance = $transactionBalance + $partiesOpeningBalance;
        
        // Total Receivable (positive balance)
        $totalReceivable = max(0, $totalBusinessBalance);
        
        // Total Payable (negative balance, shown as positive)
        $totalPayable = abs(min(0, $totalBusinessBalance));
        
        // Today's sales (invoices created today)
        $todaySales = Invoice::where('user_id', $user->id)
            ->whereDate('invoice_date', $today)
            ->sum('total_amount');
        
        // Today's payments
        $todayPayments = DB::table('payments')
            ->where('user_id', $user->id)
            ->whereDate('payment_date', $today)
            ->sum('amount');
        
        // Recent business transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('party')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Low stock products
        $lowStockProducts = Product::where('user_id', $user->id)
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->get();
        
        // Outstanding invoices
        $outstandingInvoices = Invoice::where('user_id', $user->id)
            ->where('payment_status', '!=', 'paid')
            ->with('party')
            ->orderBy('invoice_date', 'desc')
            ->limit(5)
            ->get();

        // PERSONAL METRICS
        // Personal expenses today
        $todayPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        // Personal expenses this month
        $monthPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        // Personal purchases this month
        $monthPersonalPurchases = PersonalPurchase::where('user_id', $user->id)
            ->whereYear('purchase_date', now()->year)
            ->whereMonth('purchase_date', now()->month)
            ->sum('amount');

        // Personal contacts balance summary
        $personalContacts = PersonalContact::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();
        
        $totalPersonalGiven = PersonalTransaction::where('user_id', $user->id)
            ->where('type', 'given')
            ->sum('amount');
        
        $totalPersonalReceived = PersonalTransaction::where('user_id', $user->id)
            ->where('type', 'received')
            ->sum('amount');

        // Calculate personal balances
        $personalYouOwe = 0;
        $personalTheyOwe = 0;
        foreach ($personalContacts as $contact) {
            $balance = $contact->balance;
            if ($balance < 0) {
                $personalYouOwe += abs($balance);
            } else {
                $personalTheyOwe += $balance;
            }
        }

        // Recent personal transactions
        $recentPersonalTransactions = PersonalTransaction::where('user_id', $user->id)
            ->with('personalContact')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent personal expenses
        $recentPersonalExpenses = PersonalExpense::where('user_id', $user->id)
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                // Business
                'total_receivable' => round($totalReceivable, 2),
                'total_payable' => round($totalPayable, 2),
                'today_sales' => round($todaySales, 2),
                'today_payments' => round($todayPayments, 2),
                'recent_transactions' => $recentTransactions,
                'low_stock_products' => $lowStockProducts,
                'outstanding_invoices' => $outstandingInvoices,
                
                // Personal
                'today_personal_expenses' => round($todayPersonalExpenses, 2),
                'month_personal_expenses' => round($monthPersonalExpenses, 2),
                'month_personal_purchases' => round($monthPersonalPurchases, 2),
                'personal_you_owe' => round($personalYouOwe, 2),
                'personal_they_owe' => round($personalTheyOwe, 2),
                'total_personal_given' => round($totalPersonalGiven, 2),
                'total_personal_received' => round($totalPersonalReceived, 2),
                'recent_personal_transactions' => $recentPersonalTransactions,
                'recent_personal_expenses' => $recentPersonalExpenses,
            ]
        ]);
    }
}

