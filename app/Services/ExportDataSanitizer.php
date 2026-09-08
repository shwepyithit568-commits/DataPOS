<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Standardized data sanitizer for CSV and Excel imports/exports (target-design §8 & §9).
 *
 * - Neutralizes formula injection vulnerabilities (=, +, -, @)
 * - Preserves leading zeros on phone numbers, barcodes, SKUs, and IMEIs
 * - Prevents Excel scientific notation conversion
 * - Enforces UTF-8 BOM for Windows Excel compatibility
 * - Audits export events into immutable AuditLog
 */
class ExportDataSanitizer
{
    /**
     * Characters that trigger formula execution in spreadsheet software.
     */
    protected static array $formulaTriggers = ['=', '+', '-', '@'];

    /**
     * Neutralize formula injection risk in CSV cell strings.
     */
    public static function sanitizeCsvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // If string starts with =, +, -, @, prefix with a single quote to force text interpretation
        if ($str !== '' && in_array($str[0], self::$formulaTriggers, true)) {
            // Check if it's purely a negative numeric amount (e.g. -500.00, -100)
            if ($str[0] === '-' && is_numeric($str)) {
                return $str;
            }

            return "'" . $str;
        }

        return $str;
    }

    /**
     * Sanitize an entire row of CSV values.
     *
     * @param  array<int|string, mixed>  $row
     * @return array<int|string, string>
     */
    public static function sanitizeCsvRow(array $row): array
    {
        return array_map([self::class, 'sanitizeCsvValue'], $row);
    }

    /**
     * Set Excel cell value explicitly as text string to preserve leading zeros and prevent scientific notation.
     */
    public static function setStringCell(Worksheet $sheet, string $cellCoordinate, mixed $value): void
    {
        $sheet->setCellValueExplicit(
            $cellCoordinate,
            (string) ($value ?? ''),
            DataType::TYPE_STRING
        );
    }

    /**
     * Standard UTF-8 BOM string for Windows Excel compatibility.
     */
    public static function utf8Bom(): string
    {
        return "\xEF\xBB\xBF";
    }

    /**
     * Write an audit log record for an export event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function auditExport(
        Store $store,
        string $exportType,
        ?User $actor = null,
        array $metadata = []
    ): void {
        AuditLog::write(
            storeId: $store->id,
            action: 'export.' . $exportType,
            entityType: 'export',
            entityId: null,
            metadata: array_merge([
                'export_type' => $exportType,
                'exported_at' => now()->toIso8601String(),
                'ip_address'  => request()->ip(),
            ], $metadata),
            actorId: $actor?->id,
            ipAddress: request()->ip()
        );
    }
}
