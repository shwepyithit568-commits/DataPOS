<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetImportReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, ?string>>}
     */
    public function read(string|UploadedFile $file, ?string $sheetName = null): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = strtolower($file instanceof UploadedFile ? $file->getClientOriginalExtension() : pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path, $sheetName),
            default => throw new \InvalidArgumentException('Unsupported import file type. Please upload CSV or XLSX.'),
        };
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, ?string>>}
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open uploaded file.');
        }

        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            throw new \InvalidArgumentException('Import file is empty or missing a header row.');
        }

        $headers = $this->normalizeHeaders($rawHeader);
        $rows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rows[] = $this->mapRow($headers, $row, $rowNumber);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, ?string>>}
     */
    private function readXlsx(string $path, ?string $sheetName = null): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('XLSX import requires the PHP zip extension. Enable zip or upload CSV.');
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Invalid XLSX file. Please upload a valid Excel workbook.', 0, $e);
        }

        try {
            $worksheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : null;
            // calculateFormulas=true so sheets that derive values from formulas
            // (e.g. the ACDC App_Data sheet) yield their computed/cached values
            // instead of raw formula strings like "=IF(...)".
            Calculation::getInstance()->setSuppressFormulaErrors(true);
            $rawRows = ($worksheet ?? $spreadsheet->getActiveSheet())->toArray(null, true, false, false);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if (empty($rawRows)) {
            throw new \InvalidArgumentException('Import file is empty or missing a header row.');
        }

        $rawHeader = array_shift($rawRows);
        if (!$rawHeader || $this->isBlankRow($rawHeader)) {
            throw new \InvalidArgumentException('Import file is empty or missing a header row.');
        }

        $headers = $this->normalizeHeaders($rawHeader);
        $rows = [];
        $rowNumber = 1;

        foreach ($rawRows as $row) {
            $rowNumber++;
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rows[] = $this->mapRow($headers, $row, $rowNumber);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param array<int, string|null> $rawHeader
     * @return array<int, string>
     */
    private function normalizeHeaders(array $rawHeader): array
    {
        return array_map(
            fn($header) => $this->normalizeHeader($header),
            $rawHeader
        );
    }

    /**
     * Turn human-friendly column labels into the snake_case keys the
     * importers expect. The product export writes labels such as
     * "Retail Price (Ks)" / "Stock Status"; the importers compare against
     * "retail_price" / "stock_status", so "(ks)" is stripped and every
     * remaining run of non-alphanumeric characters becomes an underscore.
     */
    private function normalizeHeader(?string $header): string
    {
        $h = strtolower(trim((string) $header));
        $h = str_replace('(ks)', '', $h);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h) ?? '';

        return trim($h, '_');
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string|null> $row
     * @return array<string, ?string>
     */
    private function mapRow(array $headers, array $row, int $rowNumber): array
    {
        $mapped = ['_row' => $rowNumber];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $mapped[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $mapped;
    }

    /**
     * @param array<int, mixed> $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
