<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KycExportService
{
    public function exportSupplierData(Supplier $supplier, string $format = 'excel'): string
    {
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($supplier);
            case 'pdf':
                return $this->exportToPdf($supplier);
            case 'csv':
                return $this->exportToCsv($supplier);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    private function exportToExcel(Supplier $supplier): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Invoice Management System')
            ->setLastModifiedBy('System')
            ->setTitle('KYC Application Data - ' . $supplier->company_name)
            ->setDescription('Complete KYC application data export');

        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Data styling
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];

        // Company Information Sheet
        $sheet->setTitle('Company Information');
        $this->addCompanyData($sheet, $supplier, $headerStyle, $dataStyle);

        // Documents Sheet
        if ($supplier->documents()->count() > 0) {
            $docSheet = $spreadsheet->createSheet();
            $docSheet->setTitle('Documents');
            $this->addDocumentsData($docSheet, $supplier, $headerStyle, $dataStyle);
        }

        // Save file
        $filename = 'kyc_export_' . $supplier->id . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filepath = 'exports/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filepath));

        return $filepath;
    }

    private function addCompanyData($sheet, Supplier $supplier, array $headerStyle, array $dataStyle): void
    {
        $data = [
            ['Field', 'Value'],
            ['Application ID', $supplier->id],
            ['Company Name', $supplier->company_name],
            ['Legal Name', $supplier->legal_name],
            ['Tax Registration Number', $supplier->tax_registration_number],
            ['Contact Email', $supplier->contact_email],
            ['Contact Phone', $supplier->contact_phone],
            ['Business Type', $supplier->business_type],
            ['Industry', $supplier->industry],
            ['Incorporation Date', $supplier->incorporation_date?->format('Y-m-d')],
            ['Country', $supplier->country],
            ['State/Province', $supplier->state_province],
            ['City', $supplier->city],
            ['Address', $supplier->address],
            ['Postal Code', $supplier->postal_code],
            ['Website', $supplier->website],
            ['KYB Status', $supplier->kyb_status],
            ['Grade', $supplier->grade],
            ['Completion Percentage', $supplier->completion_percentage . '%'],
            ['Created At', $supplier->created_at->format('Y-m-d H:i:s')],
            ['Updated At', $supplier->updated_at->format('Y-m-d H:i:s')],
            ['KYB Approved At', $supplier->kyb_approved_at?->format('Y-m-d H:i:s')],
            ['KYB Approved By', $supplier->kybApprovedBy?->name],
            ['KYB Notes', $supplier->kyb_notes],
        ];

        // Add KYC Data if exists
        if ($supplier->kyc_data) {
            foreach ($supplier->kyc_data as $key => $value) {
                $data[] = ['KYC ' . ucfirst(str_replace('_', ' ', $key)), $value];
            }
        }

        $sheet->fromArray($data, null, 'A1');

        // Apply styling
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
        $sheet->getStyle('A2:B' . count($data))->applyFromArray($dataStyle);

        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(40);
    }

    private function addDocumentsData($sheet, Supplier $supplier, array $headerStyle, array $dataStyle): void
    {
        $documents = $supplier->documents()->with('documentType')->get();

        $data = [
            ['Document ID', 'Type', 'Status', 'File Path', 'Created At', 'Reviewed At', 'Priority', 'VIP', 'Review Notes']
        ];

        foreach ($documents as $document) {
            $data[] = [
                $document->id,
                $document->documentType->name ?? 'Unknown',
                $document->status,
                $document->file_path,
                $document->created_at->format('Y-m-d H:i:s'),
                $document->reviewed_at?->format('Y-m-d H:i:s'),
                $document->priority,
                $document->vip ? 'Yes' : 'No',
                $document->review_notes
            ];
        }

        $sheet->fromArray($data, null, 'A1');

        // Apply styling
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getStyle('A2:I' . count($data))->applyFromArray($dataStyle);

        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function exportToPdf(Supplier $supplier): string
    {
        // This would require a PDF library like DomPDF or TCPDF
        // For now, we'll return a placeholder
        throw new \Exception('PDF export not implemented yet');
    }

    private function exportToCsv(Supplier $supplier): string
    {
        $filename = 'kyc_export_' . $supplier->id . '_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $filepath = 'exports/' . $filename;

        $file = fopen(storage_path('app/public/' . $filepath), 'w');

        // Add BOM for UTF-8
        fwrite($file, "\xEF\xBB\xBF");

        // Company data
        fputcsv($file, ['Field', 'Value']);
        fputcsv($file, ['Application ID', $supplier->id]);
        fputcsv($file, ['Company Name', $supplier->company_name]);
        fputcsv($file, ['Legal Name', $supplier->legal_name]);
        fputcsv($file, ['Tax Registration Number', $supplier->tax_registration_number]);
        fputcsv($file, ['Contact Email', $supplier->contact_email]);
        fputcsv($file, ['Contact Phone', $supplier->contact_phone]);
        fputcsv($file, ['Business Type', $supplier->business_type]);
        fputcsv($file, ['Industry', $supplier->industry]);
        fputcsv($file, ['Incorporation Date', $supplier->incorporation_date?->format('Y-m-d')]);
        fputcsv($file, ['Country', $supplier->country]);
        fputcsv($file, ['State/Province', $supplier->state_province]);
        fputcsv($file, ['City', $supplier->city]);
        fputcsv($file, ['Address', $supplier->address]);
        fputcsv($file, ['Postal Code', $supplier->postal_code]);
        fputcsv($file, ['Website', $supplier->website]);
        fputcsv($file, ['KYB Status', $supplier->kyb_status]);
        fputcsv($file, ['Grade', $supplier->grade]);
        fputcsv($file, ['Completion Percentage', $supplier->completion_percentage . '%']);
        fputcsv($file, ['Created At', $supplier->created_at->format('Y-m-d H:i:s')]);
        fputcsv($file, ['Updated At', $supplier->updated_at->format('Y-m-d H:i:s')]);
        fputcsv($file, ['KYB Approved At', $supplier->kyb_approved_at?->format('Y-m-d H:i:s')]);
        fputcsv($file, ['KYB Approved By', $supplier->kybApprovedBy?->name]);
        fputcsv($file, ['KYB Notes', $supplier->kyb_notes]);

        fclose($file);

        return $filepath;
    }
}
