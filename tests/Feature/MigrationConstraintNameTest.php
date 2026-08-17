<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * MySQL/MariaDB cap identifiers at 64 characters (SQLite does not enforce
 * this). Laravel auto-names foreign keys `{table}_{column}_foreign`, unique
 * constraints `{table}_{col1}_{col2}_unique` and indexes
 * `{table}_{col}_index` — so a long table/column name can produce a
 * migration that passes on SQLite (the test DB) but fails on the
 * production MySQL server.
 *
 * This static guard scans the migration files for auto-named constraints
 * and fails if any would exceed 64 characters. Use explicit short names
 * (e.g. `$table->foreign('x', 'short_fk')`) when a generated name is too
 * long. Caught in the wild: `2026_08_17_000006` — the auto FK
 * `inventory_reconciliation_items_inventory_reconciliation_id_foreign`
 * (66 chars) failed on MariaDB during the local drill rehearsal.
 */
class MigrationConstraintNameTest extends TestCase
{
    private const MAX_IDENTIFIER = 64;

    public function test_auto_constraint_names_stay_under_mysql_64_char_limit(): void
    {
        $violations = [];

        foreach (glob(database_path('migrations/*.php')) as $file) {
            $src = File::get($file);

            foreach ($this->schemaCreateBlocks($src) as $table => $block) {
                $this->collectViolations($table, $block, $violations);
            }
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    /**
     * @return array<string, string>  table name => create-block source
     */
    private function schemaCreateBlocks(string $src): array
    {
        $blocks = [];

        if (! preg_match_all('/Schema::create\(\s*[\'"]([\w_]+)[\'"]\s*,/', $src, $matches, PREG_OFFSET_CAPTURE)) {
            return $blocks;
        }

        foreach ($matches[0] as $i => $whole) {
            $table = $matches[1][$i][0];
            $start = $whole[1];
            $end = isset($matches[0][$i + 1]) ? $matches[0][$i + 1][1] : strlen($src);
            $blocks[$table] = substr($src, $start, $end - $start);
        }

        return $blocks;
    }

    /**
     * @param  array<int, string>  $violations
     */
    private function collectViolations(string $table, string $block, array &$violations): void
    {
        // Foreign keys from foreignId()->constrained() (auto name table_column_foreign).
        if (preg_match_all('/foreignId\(\s*[\'"]([\w_]+)[\'"]\s*\)\s*->constrained\(/', $block, $fkCols)) {
            foreach ($fkCols[1] as $col) {
                $name = "{$table}_{$col}_foreign";
                if (strlen($name) > self::MAX_IDENTIFIER) {
                    $violations[] = "[$table] auto FK name '{$name}' = " . strlen($name)
                        . ' chars (max ' . self::MAX_IDENTIFIER . ') — give it an explicit short name.';
                }
            }
        }

        // Multi-column unique() without an explicit name (auto name table_c1_c2_unique).
        if (preg_match_all('/->unique\(\[\s*[\'"]([\w_]+)[\'"]\s*,\s*[\'"]([\w_]+)[\'"]/', $block, $uniqCols)) {
            foreach ($uniqCols[0] as $i => $_) {
                $name = "{$table}_{$uniqCols[1][$i]}_{$uniqCols[2][$i]}_unique";
                if (strlen($name) > self::MAX_IDENTIFIER) {
                    $violations[] = "[$table] auto unique name '{$name}' = " . strlen($name)
                        . ' chars (max ' . self::MAX_IDENTIFIER . ') — give it an explicit name.';
                }
            }
        }

        // Multi-column index() without an explicit name (auto name table_c1_c2_index).
        if (preg_match_all('/->index\(\[\s*[\'"]([\w_]+)[\'"]\s*,\s*[\'"]([\w_]+)[\'"]/', $block, $idxCols)) {
            foreach ($idxCols[0] as $i => $_) {
                $name = "{$table}_{$idxCols[1][$i]}_{$idxCols[2][$i]}_index";
                if (strlen($name) > self::MAX_IDENTIFIER) {
                    $violations[] = "[$table] auto index name '{$name}' = " . strlen($name)
                        . ' chars (max ' . self::MAX_IDENTIFIER . ') — give it an explicit name.';
                }
            }
        }
    }
}
