<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SOATemplateExport implements WithEvents
{
    protected $client;
    protected $reservations;

    public function __construct($client, $reservations)
    {
        $this->client = $client;
        $this->reservations = $reservations;
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            /*
            ============================
            HEADER / TOP DETAILS
            ============================
            */

            $sheet->setCellValue('A15', 'Date: ' . now()->format('d/m/Y'));
            $sheet->setCellValue('A17', 'To:');
            $sheet->setCellValue('A18', $this->client->name ?? '');

            /*
            ============================
            TABLE HEADER
            ============================
            */

            $sheet->setCellValue('A24', 'Date');
            $sheet->setCellValue('B24', 'PARTICULARS');
            $sheet->setCellValue('C24', 'QTY');
            $sheet->setCellValue('D24', 'UNIT');
            $sheet->setCellValue('E24', 'RATE');
            $sheet->setCellValue('F24', 'AMOUNT');

            $sheet->getStyle('A24:F24')->getFont()->setBold(true);

            /*
            ============================
            TABLE DATA
            Start at row 25
            ============================
            */

            $startRow = 25;
            $currentRow = $startRow;
            $subtotal = 0;

            foreach ($this->reservations as $r) {

                $days = $r['days'] ?? 1;
                $amount = $r['total_price'] ?? 0;
                $rate = $days > 0 ? $amount / $days : $amount;

                $sheet->setCellValue("A{$currentRow}", $r['check_in'] ?? '');
                $sheet->setCellValue("B{$currentRow}", $r['name'] ?? '');
                $sheet->setCellValue("C{$currentRow}", $r['pax'] ?? 1);
                $sheet->setCellValue("D{$currentRow}", $days . ' day');
                $sheet->setCellValue("E{$currentRow}", $rate);
                $sheet->setCellValue("F{$currentRow}", $amount);

                $subtotal += $amount;
                $currentRow++;
            }

            /*
            ============================
            SUMMARY VALUES
            Column E = labels
            Column F = values
            ============================
            */

            $sheet->setCellValue('F15', $subtotal);
            $sheet->setCellValue('F16', 0);
            $sheet->setCellValue('F17', 0);
            $sheet->setCellValue('F18', $subtotal);

            /*
            ============================
            NUMBER FORMATTING
            ============================
            */

            for ($row = $startRow; $row < $currentRow; $row++) {

                $sheet->getStyle("E{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('"₱"#,##0.00');

                $sheet->getStyle("F{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('"₱"#,##0.00');
            }

            $sheet->getStyle('F15:F18')
                ->getNumberFormat()
                ->setFormatCode('"₱"#,##0.00');

            /*
            ============================
            BORDERS
            ============================
            */

            $tableRange = "A24:F" . max($currentRow - 1, 24);

            $sheet->getStyle($tableRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            $summaryRange = 'E15:F18';

            $sheet->getStyle($summaryRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            /*
            ============================
            COLUMN WIDTHS
            ============================
            */

            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(18);
        },
    ];
}
}