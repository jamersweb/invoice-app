<?php

namespace App\Services;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Funding\Models\Funding;
use App\Modules\Repayments\Models\ReceivedRepayment;
use App\Modules\Repayments\Models\ExpectedRepayment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    public function exportInvoices(array $filters = [], string $format = 'excel'): string
    {
        $query = Invoice::query()->with(['supplier', 'buyer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (isset($filters['buyer_id'])) {
            $query->where('buyer_id', $filters['buyer_id']);
        }
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return $format === 'csv' 
            ? $this->invoicesToCsv($invoices)
            : $this->invoicesToExcel($invoices);
    }

    public function exportFundings(array $filters = [], string $format = 'excel'): string
    {
        $query = Funding::query()->with(['invoice.supplier', 'invoice.buyer', 'offer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $fundings = $query->orderBy('created_at', 'desc')->get();

        return $format === 'csv'
            ? $this->fundingsToCsv($fundings)
            : $this->fundingsToExcel($fundings);
    }

    public function exportRepayments(array $filters = [], string $format = 'excel'): string
    {
        $query = ReceivedRepayment::query()->with(['buyer']);

        if (isset($filters['buyer_id'])) {
            $query->where('buyer_id', $filters['buyer_id']);
        }
        if (isset($filters['from_date'])) {
            $query->whereDate('received_date', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('received_date', '<=', $filters['to_date']);
        }

        $repayments = $query->orderBy('received_date', 'desc')->get();

        return $format === 'csv'
            ? $this->repaymentsToCsv($repayments)
            : $this->repaymentsToExcel($repayments);
    }

    private function invoicesToExcel($invoices): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoices');

        $headers = [
            'ID', 'Supplier ID', 'Supplier Name', 'Buyer ID', 'Buyer Name',
            'Invoice Number', 'Amount', 'Currency', 'Due Date', 'Status',
            'OCR Confidence', 'Is Duplicate', 'Priority', 'Assigned To',
            'Reviewed By', 'Reviewed At', 'Created At'
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->fromArray([[
                $invoice->id,
                $invoice->supplier_id,
                $invoice->supplier->company_name ?? '',
                $invoice->buyer_id,
                $invoice->buyer->name ?? '',
                $invoice->invoice_number,
                $invoice->amount,
                $invoice->currency,
                $invoice->due_date?->toDateString(),
                $invoice->status,
                $invoice->ocr_confidence,
                $invoice->is_duplicate_flag ? 'Yes' : 'No',
                $invoice->priority,
                $invoice->assigned_to,
                $invoice->reviewed_by,
                $invoice->reviewed_at?->toDateTimeString(),
                $invoice->created_at->toDateTimeString(),
            ]], null, "A{$row}");
            $row++;
        }

        $filename = 'invoices_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filepath = 'exports/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filepath));

        return $filepath;
    }

    private function invoicesToCsv($invoices): string
    {
        $filename = 'invoices_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $filepath = 'exports/' . $filename;
        $file = fopen(storage_path('app/public/' . $filepath), 'w');

        fputcsv($file, ['ID', 'Supplier ID', 'Supplier Name', 'Buyer ID', 'Buyer Name',
            'Invoice Number', 'Amount', 'Currency', 'Due Date', 'Status',
            'OCR Confidence', 'Is Duplicate', 'Priority', 'Assigned To',
            'Reviewed By', 'Reviewed At', 'Created At']);

        foreach ($invoices as $invoice) {
            fputcsv($file, [
                $invoice->id,
                $invoice->supplier_id,
                $invoice->supplier->company_name ?? '',
                $invoice->buyer_id,
                $invoice->buyer->name ?? '',
                $invoice->invoice_number,
                $invoice->amount,
                $invoice->currency,
                $invoice->due_date?->toDateString(),
                $invoice->status,
                $invoice->ocr_confidence,
                $invoice->is_duplicate_flag ? 'Yes' : 'No',
                $invoice->priority,
                $invoice->assigned_to,
                $invoice->reviewed_by,
                $invoice->reviewed_at?->toDateTimeString(),
                $invoice->created_at->toDateTimeString(),
            ]);
        }

        fclose($file);
        return $filepath;
    }

    private function fundingsToExcel($fundings): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fundings');

        $headers = ['ID', 'Invoice ID', 'Offer ID', 'Amount', 'Status', 
            'Batch ID', 'Funded At', 'Created At'];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($fundings as $funding) {
            $sheet->fromArray([[
                $funding->id,
                $funding->invoice_id,
                $funding->offer_id,
                $funding->amount,
                $funding->status,
                $funding->batch_id,
                $funding->funded_at?->toDateTimeString(),
                $funding->created_at->toDateTimeString(),
            ]], null, "A{$row}");
            $row++;
        }

        $filename = 'fundings_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filepath = 'exports/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filepath));

        return $filepath;
    }

    private function fundingsToCsv($fundings): string
    {
        $filename = 'fundings_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $filepath = 'exports/' . $filename;
        $file = fopen(storage_path('app/public/' . $filepath), 'w');

        fputcsv($file, ['ID', 'Invoice ID', 'Offer ID', 'Amount', 'Status', 
            'Batch ID', 'Funded At', 'Created At']);

        foreach ($fundings as $funding) {
            fputcsv($file, [
                $funding->id,
                $funding->invoice_id,
                $funding->offer_id,
                $funding->amount,
                $funding->status,
                $funding->batch_id,
                $funding->funded_at?->toDateTimeString(),
                $funding->created_at->toDateTimeString(),
            ]);
        }

        fclose($file);
        return $filepath;
    }

    private function repaymentsToExcel($repayments): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Repayments');

        $headers = ['ID', 'Buyer ID', 'Buyer Name', 'Amount', 'Received Date',
            'Bank Reference', 'Allocated Amount', 'Unallocated Amount', 'Created At'];

        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($repayments as $repayment) {
            $sheet->fromArray([[
                $repayment->id,
                $repayment->buyer_id,
                $repayment->buyer->name ?? '',
                $repayment->amount,
                $repayment->received_date?->toDateString(),
                $repayment->bank_reference,
                $repayment->allocated_amount,
                $repayment->unallocated_amount,
                $repayment->created_at->toDateTimeString(),
            ]], null, "A{$row}");
            $row++;
        }

        $filename = 'repayments_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filepath = 'exports/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filepath));

        return $filepath;
    }

    private function repaymentsToCsv($repayments): string
    {
        $filename = 'repayments_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $filepath = 'exports/' . $filename;
        $file = fopen(storage_path('app/public/' . $filepath), 'w');

        fputcsv($file, ['ID', 'Buyer ID', 'Buyer Name', 'Amount', 'Received Date',
            'Bank Reference', 'Allocated Amount', 'Unallocated Amount', 'Created At']);

        foreach ($repayments as $repayment) {
            fputcsv($file, [
                $repayment->id,
                $repayment->buyer_id,
                $repayment->buyer->name ?? '',
                $repayment->amount,
                $repayment->received_date?->toDateString(),
                $repayment->bank_reference,
                $repayment->allocated_amount,
                $repayment->unallocated_amount,
                $repayment->created_at->toDateTimeString(),
            ]);
        }

        fclose($file);
        return $filepath;
    }
}






