<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExcelToPdfService
{
    public function convertExcelToPdf($excelPath, $pdfPath)
    {
        // Load Excel file
        $spreadsheet = IOFactory::load($excelPath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Convert Excel to HTML
        $html = $this->convertExcelToHtml($worksheet);

        // Convert HTML to PDF
        $this->convertHtmlToPdf($html, $pdfPath);
    }

    private function convertExcelToHtml($worksheet)
    {
        $html = '<table style="border-collapse: collapse; width: 100%;">';
        
        // Get highest row and column
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Add table headers
        $html .= '<thead><tr>';
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
            $html .= '<th style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($cellValue) . '</th>';
        }
        $html .= '</tr></thead>';

        // Add table body
        $html .= '<tbody>';
        for ($row = 2; $row <= $highestRow; $row++) {
            $html .= '<tr>';
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($cellValue) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function convertHtmlToPdf($html, $pdfPath)
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Save the PDF
        file_put_contents($pdfPath, $dompdf->output());
    }
} 