<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkbookTimeNormalizer
{
    /**
     * Menyalin berkas Excel dan menormalisasi kolom CATATAN WAKTU di lembar PESERTA.
     * Berkas CSV dikembalikan tanpa perubahan.
     */
    public function normalize(string $sourcePath, string $extension, string $destinationPath): string
    {
        if (strtolower($extension) === 'csv') {
            copy($sourcePath, $destinationPath);

            return $destinationPath;
        }

        $spreadsheet = IOFactory::load($sourcePath);
        $sheet = $spreadsheet->getSheetByName('PESERTA');

        if ($sheet instanceof Worksheet) {
            foreach ($sheet->getRowIterator(2) as $row) {
                $index = $row->getRowIndex();
                $time = $this->excelTimeToSeed($sheet->getCell('H'.$index)->getValue());

                if ($time !== '') {
                    $sheet->getCell('H'.$index)->setValueExplicit($time, DataType::TYPE_STRING);
                }
            }
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($destinationPath);
        $spreadsheet->disconnectWorksheets();

        return $destinationPath;
    }

    public function excelTimeToSeed(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 1) {
            $totalSeconds = (int) round(((float) $value) * 86_400);
            $hours = intdiv($totalSeconds, 3600);
            $minutes = intdiv($totalSeconds % 3600, 60);
            $seconds = $totalSeconds % 60;

            if ($hours === 0 && $minutes === 0) {
                return sprintf('00:%02d.00', $seconds);
            }

            if ($hours === 0) {
                return sprintf('00:%02d.%02d', $minutes, $seconds);
            }

            return sprintf('%02d:%02d.%02d', $hours, $minutes, $seconds);
        }

        $text = trim((string) $value);

        if (preg_match('/^(\d{1,2})[.](\d{2})[.](\d{2,3})$/', $text, $matches) === 1) {
            return sprintf('%02d:%02d.%s', (int) $matches[1], (int) $matches[2], $matches[3]);
        }

        return $text;
    }
}
