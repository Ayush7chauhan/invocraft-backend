<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\PersonalExpense;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Build base report data structure.
     */
    private function baseReportData(): array
    {
        return [
            'period' => '',
            'total_sales' => 0,
            'total_purchases' => 0,
            'total_receivable' => 0,
            'total_payable' => 0,
            'total_transactions' => 0,
            'total_invoices' => 0,
            'total_customers' => 0,
            'total_products' => 0,
            'net_profit' => 0,
            'expenses' => 0,
            'total_credit' => 0,
            'total_debit' => 0,
        ];
    }

    /**
     * Get current receivable/payable from transactions + party opening balances (same as dashboard).
     */
    private function getReceivablePayable($userId): array
    {
        $transactionBalance = Transaction::where('user_id', $userId)
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        $partiesOpeningBalance = Party::where('user_id', $userId)
            ->selectRaw('SUM(opening_balance) as total')
            ->value('total') ?? 0;

        $totalBalance = $transactionBalance + $partiesOpeningBalance;
        $receivable = max(0, $totalBalance);
        $payable = abs(min(0, $totalBalance));

        return [$receivable, $payable];
    }

    /**
     * Daily report: GET /reports/daily?date=Y-m-d
     */
    public function daily(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $user = $request->user();
        $date = $request->query('date');

        $totalSales = Invoice::where('user_id', $user->id)
            ->whereDate('invoice_date', $date)
            ->sum('total_amount');

        $totalTransactions = Transaction::where('user_id', $user->id)
            ->whereDate('transaction_date', $date)
            ->count();

        $totalInvoices = Invoice::where('user_id', $user->id)
            ->whereDate('invoice_date', $date)
            ->count();

        $expenses = PersonalExpense::where('user_id', $user->id)
            ->whereDate('expense_date', $date)
            ->sum('amount');

        list($totalReceivable, $totalPayable) = $this->getReceivablePayable($user->id);

        $totalCustomers = Party::where('user_id', $user->id)->where('type', 'customer')->count();
        $totalProducts = Product::where('user_id', $user->id)->count();

        $data = $this->baseReportData();
        $data['period'] = 'daily';
        $data['total_sales'] = round($totalSales, 2);
        $data['total_receivable'] = round($totalReceivable, 2);
        $data['total_payable'] = round($totalPayable, 2);
        $data['total_transactions'] = (int) $totalTransactions;
        $data['total_invoices'] = (int) $totalInvoices;
        $data['total_customers'] = (int) $totalCustomers;
        $data['total_products'] = (int) $totalProducts;
        $data['expenses'] = round($expenses, 2);
        $data['net_profit'] = round($totalSales - $expenses, 2);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Weekly report: GET /reports/weekly?week_start=Y-m-d
     */
    public function weekly(Request $request)
    {
        $request->validate(['week_start' => 'required|date']);
        $user = $request->user();
        $weekStart = $request->query('week_start');
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $totalSales = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$weekStart, $weekEnd])
            ->sum('total_amount');

        $totalTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$weekStart, $weekEnd])
            ->count();

        $totalInvoices = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$weekStart, $weekEnd])
            ->count();

        $expenses = PersonalExpense::where('user_id', $user->id)
            ->whereBetween('expense_date', [$weekStart, $weekEnd])
            ->sum('amount');

        list($totalReceivable, $totalPayable) = $this->getReceivablePayable($user->id);

        $totalCustomers = Party::where('user_id', $user->id)->where('type', 'customer')->count();
        $totalProducts = Product::where('user_id', $user->id)->count();

        $data = $this->baseReportData();
        $data['period'] = 'weekly';
        $data['total_sales'] = round($totalSales, 2);
        $data['total_receivable'] = round($totalReceivable, 2);
        $data['total_payable'] = round($totalPayable, 2);
        $data['total_transactions'] = (int) $totalTransactions;
        $data['total_invoices'] = (int) $totalInvoices;
        $data['total_customers'] = (int) $totalCustomers;
        $data['total_products'] = (int) $totalProducts;
        $data['expenses'] = round($expenses, 2);
        $data['net_profit'] = round($totalSales - $expenses, 2);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Monthly report: GET /reports/monthly?month=Y-m
     */
    public function monthly(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $user = $request->user();
        $month = $request->query('month');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $totalSales = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->sum('total_amount');

        $totalTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->count();

        $totalInvoices = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->count();

        $expenses = PersonalExpense::where('user_id', $user->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        list($totalReceivable, $totalPayable) = $this->getReceivablePayable($user->id);

        $totalCustomers = Party::where('user_id', $user->id)->where('type', 'customer')->count();
        $totalProducts = Product::where('user_id', $user->id)->count();

        $data = $this->baseReportData();
        $data['period'] = 'monthly';
        $data['total_sales'] = round($totalSales, 2);
        $data['total_receivable'] = round($totalReceivable, 2);
        $data['total_payable'] = round($totalPayable, 2);
        $data['total_transactions'] = (int) $totalTransactions;
        $data['total_invoices'] = (int) $totalInvoices;
        $data['total_customers'] = (int) $totalCustomers;
        $data['total_products'] = (int) $totalProducts;
        $data['expenses'] = round($expenses, 2);
        $data['net_profit'] = round($totalSales - $expenses, 2);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Summary report: GET /reports/summary (all-time)
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $totalSales = Invoice::where('user_id', $user->id)->sum('total_amount');
        $totalTransactions = Transaction::where('user_id', $user->id)->count();
        $totalInvoices = Invoice::where('user_id', $user->id)->count();
        $expenses = PersonalExpense::where('user_id', $user->id)->sum('amount');

        list($totalReceivable, $totalPayable) = $this->getReceivablePayable($user->id);

        $totalCustomers = Party::where('user_id', $user->id)->where('type', 'customer')->count();
        $totalProducts = Product::where('user_id', $user->id)->count();

        $data = $this->baseReportData();
        $data['period'] = 'summary';
        $data['total_sales'] = round($totalSales, 2);
        $data['total_receivable'] = round($totalReceivable, 2);
        $data['total_payable'] = round($totalPayable, 2);
        $data['total_transactions'] = (int) $totalTransactions;
        $data['total_invoices'] = (int) $totalInvoices;
        $data['total_customers'] = (int) $totalCustomers;
        $data['total_products'] = (int) $totalProducts;
        $data['expenses'] = round($expenses, 2);
        $data['net_profit'] = round($totalSales - $expenses, 2);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Report by date range: GET /reports/range?start_date=Y-m-d&end_date=Y-m-d
     * Returns same metrics as daily/weekly/monthly plus total_credit and total_debit for the period.
     */
    public function range(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $user = $request->user();
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $totalSales = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->sum('total_amount');

        $totalTransactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->count();

        $totalInvoices = Invoice::where('user_id', $user->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->count();

        $expenses = PersonalExpense::where('user_id', $user->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $totalCredit = Transaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $totalDebit = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        list($totalReceivable, $totalPayable) = $this->getReceivablePayable($user->id);

        $totalCustomers = Party::where('user_id', $user->id)->where('type', 'customer')->count();
        $totalProducts = Product::where('user_id', $user->id)->count();

        $data = $this->baseReportData();
        $data['period'] = 'range';
        $data['total_sales'] = round($totalSales, 2);
        $data['total_receivable'] = round($totalReceivable, 2);
        $data['total_payable'] = round($totalPayable, 2);
        $data['total_transactions'] = (int) $totalTransactions;
        $data['total_invoices'] = (int) $totalInvoices;
        $data['total_customers'] = (int) $totalCustomers;
        $data['total_products'] = (int) $totalProducts;
        $data['expenses'] = round($expenses, 2);
        $data['net_profit'] = round($totalSales - $expenses, 2);
        $data['total_credit'] = round($totalCredit, 2);
        $data['total_debit'] = round($totalDebit, 2);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Chart data for Reports: time-series for sales, profit, expenses.
     * GET /reports/chart?period=monthly&months=6  or  ?period=daily&days=7  or  ?period=weekly&weeks=4
     * Or ?start_date=Y-m-d&end_date=Y-m-d for a custom range (points by day or by week if range > 14 days).
     */
    public function chart(Request $request)
    {
        $user = $request->user();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $items = [];

        if ($startDate && $endDate) {
            $start = $startDate;
            $end = $endDate;
            $daysDiff = (strtotime($end) - strtotime($start)) / 86400;
            $byWeek = $daysDiff > 14;
            if ($byWeek) {
                $current = strtotime('monday this week', strtotime($start));
                $endTs = strtotime($end);
                while ($current <= $endTs) {
                    $weekStart = date('Y-m-d', $current);
                    $weekEnd = date('Y-m-d', min($current + 6 * 86400, $endTs));
                    $totalSales = Invoice::where('user_id', $user->id)
                        ->whereBetween('invoice_date', [$weekStart, $weekEnd])
                        ->sum('total_amount');
                    $expenses = PersonalExpense::where('user_id', $user->id)
                        ->whereBetween('expense_date', [$weekStart, $weekEnd])
                        ->sum('amount');
                    $items[] = [
                        'label' => date('d M', $current),
                        'period' => $weekStart,
                        'total_sales' => round($totalSales, 2),
                        'expenses' => round($expenses, 2),
                        'net_profit' => round($totalSales - $expenses, 2),
                    ];
                    $current += 7 * 86400;
                }
            } else {
                $current = strtotime($start);
                $endTs = strtotime($end);
                while ($current <= $endTs) {
                    $date = date('Y-m-d', $current);
                    $totalSales = Invoice::where('user_id', $user->id)
                        ->whereDate('invoice_date', $date)
                        ->sum('total_amount');
                    $expenses = PersonalExpense::where('user_id', $user->id)
                        ->whereDate('expense_date', $date)
                        ->sum('amount');
                    $items[] = [
                        'label' => date('d M', $current),
                        'period' => $date,
                        'total_sales' => round($totalSales, 2),
                        'expenses' => round($expenses, 2),
                        'net_profit' => round($totalSales - $expenses, 2),
                    ];
                    $current += 86400;
                }
            }
            return response()->json(['success' => true, 'data' => $items]);
        }

        $period = $request->query('period', 'monthly');
        $months = (int) $request->query('months', 6);
        $days = (int) $request->query('days', 7);
        $weeks = (int) $request->query('weeks', 4);

        if ($period === 'daily' && $days > 0) {
            $days = min($days, 31);
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $totalSales = Invoice::where('user_id', $user->id)
                    ->whereDate('invoice_date', $date)
                    ->sum('total_amount');
                $expenses = PersonalExpense::where('user_id', $user->id)
                    ->whereDate('expense_date', $date)
                    ->sum('amount');
                $items[] = [
                    'label' => date('d M', strtotime($date)),
                    'period' => $date,
                    'total_sales' => round($totalSales, 2),
                    'expenses' => round($expenses, 2),
                    'net_profit' => round($totalSales - $expenses, 2),
                ];
            }
        } elseif ($period === 'weekly' && $weeks > 0) {
            $weeks = min($weeks, 12);
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $weekStart = date('Y-m-d', strtotime("monday this week - $i weeks"));
                $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
                $totalSales = Invoice::where('user_id', $user->id)
                    ->whereBetween('invoice_date', [$weekStart, $weekEnd])
                    ->sum('total_amount');
                $expenses = PersonalExpense::where('user_id', $user->id)
                    ->whereBetween('expense_date', [$weekStart, $weekEnd])
                    ->sum('amount');
                $items[] = [
                    'label' => 'W' . (count($items) + 1),
                    'period' => $weekStart,
                    'total_sales' => round($totalSales, 2),
                    'expenses' => round($expenses, 2),
                    'net_profit' => round($totalSales - $expenses, 2),
                ];
            }
        } else {
            $months = min($months, 24);
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("first day of -$i months"));
                $start = $month . '-01';
                $end = date('Y-m-t', strtotime($start));
                $totalSales = Invoice::where('user_id', $user->id)
                    ->whereBetween('invoice_date', [$start, $end])
                    ->sum('total_amount');
                $expenses = PersonalExpense::where('user_id', $user->id)
                    ->whereBetween('expense_date', [$start, $end])
                    ->sum('amount');
                $items[] = [
                    'label' => date('M Y', strtotime($start)),
                    'period' => $month,
                    'total_sales' => round($totalSales, 2),
                    'expenses' => round($expenses, 2),
                    'net_profit' => round($totalSales - $expenses, 2),
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $items]);
    }
}
