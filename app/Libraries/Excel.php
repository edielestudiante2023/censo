<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Helper para generar archivos .xlsx.
 * Cada hoja: ['name' => string, 'headers' => string[], 'rows' => array[]]
 */
class Excel
{
    public static function build(array $sheets): string
    {
        $ss = new Spreadsheet();
        $ss->removeSheetByIndex(0);

        foreach ($sheets as $sheet) {
            $ws = $ss->createSheet();
            $ws->setTitle(mb_substr((string) ($sheet['name'] ?? 'Hoja'), 0, 31));

            $headers = $sheet['headers'] ?? [];
            $rows    = $sheet['rows'] ?? [];
            $startRow = 1;

            if ($headers !== []) {
                $ws->fromArray($headers, null, 'A1');
                $lastCol = Coordinate::stringFromColumnIndex(count($headers));
                $style   = $ws->getStyle("A1:{$lastCol}1");
                $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
                $startRow = 2;
            }

            if ($rows !== []) {
                $ws->fromArray($rows, null, 'A' . $startRow);
            }

            $maxCols = count($headers);
            foreach ($rows as $r) {
                $maxCols = max($maxCols, count($r));
            }
            for ($c = 1; $c <= max(1, $maxCols); $c++) {
                $ws->getColumnDimensionByColumn($c)->setAutoSize(true);
            }
        }

        if ($ss->getSheetCount() === 0) {
            $ss->createSheet();
        }
        $ss->setActiveSheetIndex(0);

        $writer = new Xlsx($ss);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }
}
