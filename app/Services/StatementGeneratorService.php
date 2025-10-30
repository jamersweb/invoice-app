<?php

namespace App\Services;

use App\Models\Supplier;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Funding\Models\Funding;
use App\Modules\Repayments\Models\ExpectedRepayment;
use App\Modules\Repayments\Models\ReceivedRepayment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StatementGeneratorService
{
    public function generateStatement(Supplier $supplier, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): string
    {
        $from = $from ?? now()->subYear();
        $to = $to ?? now();

        $invoices = Invoice::where('supplier_id', $supplier->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $fundings = Funding::whereHas('invoice', function($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->whereBetween('created_at', [$from, $to])->get();

        $expectedRepayments = ExpectedRepayment::whereHas('invoice', function($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->whereBetween('due_date', [$from, $to])
          ->with('invoice.supplier')
          ->get();

        $spreadsheet = new Spreadsheet();
        
        // Summary Sheet
        $this->addSummarySheet($spreadsheet, $supplier, $from, $to, $invoices, $fundings, $expectedRepayments);
        
        // Invoices Sheet
        $this->addInvoicesSheet($spreadsheet, $invoices);
        
        // Fundings Sheet
        $this->addFundingsSheet($spreadsheet, $fundings);
        
        // Repayments Sheet
        $this->addRepaymentsSheet($spreadsheet, $expectedRepayments);

        $filename = 'statement_' . $supplier->id . '_' . now()->format('Y_m_d') . '.xlsx';
        $filepath = 'exports/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filepath));

        return $filepath;
    }

    private function addSummarySheet($spreadsheet, $supplier, $from, $to, $invoices, $fundings, $expectedRepayments)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');

        $totalInvoiceAmount = $invoices->sum('amount');
        $totalFunded = $fundings->sum('amount');
        $totalExpected = $expectedRepayments->sum('amount');
        $paid = $expectedRepayments->where('status', 'settled')->sum('amount');
        $outstanding = $totalExpected - $paid;

        $data = [
            ['Supplier Statement', ''],
            ['Company Name', $supplier->company_name],
            ['Period', $from->format('Y-m-d') . ' to ' . $to->format('Y-m-d')],
            ['Generated', now()->format('Y-m-d H:i:s')],
            [''],
            ['Summary', ''],
            ['Total Invoices', $invoices->count()],
            ['Total Invoice Amount', $totalInvoiceAmount],
            ['Total Funded', $totalFunded],
            ['Total Expected Repayments', $totalExpected],
            ['Paid', $paid],
            ['Outstanding', $outstanding],
        ];

        $sheet->fromArray($data, null, 'A1');
    }

    private function addInvoicesSheet($spreadsheet, $invoices)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Invoices');

        $headers = ['ID', 'Invoice Number', 'Amount', 'Currency', 'Due Date', 'Status', 'Created At'];
        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->fromArray([[
                $invoice->id,
                $invoice->invoice_number,
                $invoice->amount,
                $invoice->currency,
                $invoice->due_date?->toDateString(),
                $invoice->status,
                $invoice->created_at->toDateTimeString(),
            ]], null, "A{$row}");
            $row++;
        }
    }

    private function addFundingsSheet($spreadsheet, $fundings)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Fundings');

        $headers = ['ID', 'Invoice ID', 'Amount', 'Status', 'Funded At'];
        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($fundings as $funding) {
            $sheet->fromArray([[
                $funding->id,
                $funding->invoice_id,
                $funding->amount,
                $funding->status,
                $funding->funded_at?->toDateTimeString(),
            ]], null, "A{$row}");
            $row++;
        }
    }

    private function addRepaymentsSheet($spreadsheet, $expectedRepayments)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Repayments');

        $headers = ['ID', 'Invoice ID', 'Amount', 'Due Date', 'Status'];
        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($expectedRepayments as $repayment) {
            $sheet->fromArray([[
                $repayment->id,
                $repayment->invoice_id,
                $repayment->amount,
                $repayment->due_date?->toDateString(),
                $repayment->status,
            ]], null, "A{$row}");
            $row++;
        }
    }
}
