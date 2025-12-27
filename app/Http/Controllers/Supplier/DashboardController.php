<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Repayments\Models\RepaymentAllocation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('user_id', $user->id)->first();

        if (!$supplier) {
            return response()->json([
                'kpis' => [
                    'totalFunded' => 0,
                    'totalRepaid' => 0,
                    'outstanding' => 0,
                    'overdue' => 0
                ],
                'series' => []
            ]);
        }

        // 1. KPIs
        // Total Funded: Sum of funded_amount for financed invoices
        $totalFunded = Invoice::where('supplier_id', $supplier->id)
            ->whereNotNull('funded_amount')
            ->sum('funded_amount');

        // Total Repaid: Sum of allocated repayments for this supplier's invoices
        $totalRepaid = RepaymentAllocation::join('expected_repayments', 'repayment_allocations.expected_repayment_id', '=', 'expected_repayments.id')
            ->join('invoices', 'expected_repayments.invoice_id', '=', 'invoices.id')
            ->where('invoices.supplier_id', $supplier->id)
            ->sum('repayment_allocations.amount');

        // Outstanding: Financed/Partial status sum(amount) - or simpler, total invoice amount - total repaid
        // To be safe, let's use Invoice amount for non-paid invoices who are financed.
        // Actually, logic: Outstanding = Financed Amount - Repaid Amount?
        // Or Outstanding = Sum of (Amount) of invoices that are Open/Partially Paid?
        // Let's use: Sum of amount of strictly financed/unpaid invoices.
        $outstanding = Invoice::where('supplier_id', $supplier->id)
            ->whereIn('status', ['financed', 'partially_paid', 'issued']) // Assuming 'issued' count too?
            ->sum('amount') - $totalRepaid;
        if ($outstanding < 0) $outstanding = 0;

        // Overdue: Due date passed and not paid
        $overdue = Invoice::where('supplier_id', $supplier->id)
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'repaid', 'draft', 'cancelled'])
            ->sum('amount');

        // 2. Series (Last 30 Days)
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Funded Series
        $fundedSeries = Invoice::where('supplier_id', $supplier->id)
            ->whereBetween('funded_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE(funded_date) as date'),
                DB::raw('SUM(funded_amount) as value')
            ]);

        // Repaid Series
        $repaidSeries = RepaymentAllocation::join('expected_repayments', 'repayment_allocations.expected_repayment_id', '=', 'expected_repayments.id')
            ->join('invoices', 'expected_repayments.invoice_id', '=', 'invoices.id')
            ->join('received_repayments', 'repayment_allocations.received_repayment_id', '=', 'received_repayments.id')
            ->where('invoices.supplier_id', $supplier->id)
            ->whereBetween('received_repayments.received_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE(received_repayments.received_date) as date'),
                DB::raw('SUM(repayment_allocations.amount) as value')
            ]);

        // Merge Series
        $dates = [];
        for ($i = 0; $i <= 30; $i++) {
            $d = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dates[$d] = ['date' => $d, 'funded' => 0, 'repaid' => 0];
        }

        foreach ($fundedSeries as $item) {
            if (isset($dates[$item->date])) $dates[$item->date]['funded'] = (float)$item->value;
        }
        foreach ($repaidSeries as $item) {
            if (isset($dates[$item->date])) $dates[$item->date]['repaid'] = (float)$item->value;
        }

        return response()->json([
            'kpis' => [
                'totalFunded' => (float)$totalFunded,
                'totalRepaid' => (float)$totalRepaid,
                'outstanding' => (float)$outstanding,
                'overdue' => (float)$overdue
            ],
            'series' => array_values($dates)
        ]);
    }

    public function paymentStats(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('user_id', $user->id)->first();

        if (!$supplier) {
            return response()->json([
                'total' => 0,
                'paid' => 0,
                'partiallyPaid' => 0,
                'overdue' => 0
            ]);
        }

        $query = Invoice::where('supplier_id', $supplier->id);

        $total = (clone $query)->sum('amount');
        $paid = (clone $query)->whereIn('status', ['paid', 'repaid'])->sum('amount');
        $partiallyPaid = (clone $query)->where('status', 'partially_paid')->sum('amount');
        $overdue = (clone $query)->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'repaid', 'draft', 'cancelled'])
            ->sum('amount');

        return response()->json([
            'total' => (float)$total,
            'paid' => (float)$paid,
            'partiallyPaid' => (float)$partiallyPaid,
            'overdue' => (float)$overdue
        ]);
    }
}
