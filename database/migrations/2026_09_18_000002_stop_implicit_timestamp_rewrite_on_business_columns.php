<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stop MariaDB from rewriting business timestamps on every UPDATE.
     *
     * On MySQL/MariaDB with `explicit_defaults_for_timestamp=OFF` (still the
     * default on MariaDB 10.4), the FIRST TIMESTAMP column of a table that is
     * declared without an explicit DEFAULT or ON UPDATE clause silently gets
     *   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
     * added by the server. Laravel's `$table->timestamp('opened_at')` is exactly
     * such a declaration, so two business timestamps ended up self-updating:
     *
     *   cashier_shifts.opened_at     — the shift's business day
     *   inventory_movements.occurred_at — when a stock movement happened
     *
     * The effect is silent history rewriting, and it is not cosmetic:
     *  - Logging a cash event or closing a shift UPDATEs the row, which moves
     *    `opened_at` to "now". A shift opened on the 17th and touched after
     *    midnight becomes a shift that "opened" on the 18th, so the 17th's daily
     *    closing finds NO shifts: the opening float, cash sales and cash-out of
     *    that drawer disappear from the day it belongs to and the drawer is
     *    reconciled against the wrong figures.
     *  - Any update to an inventory movement (the ledger is the single source of
     *    truth for stock) rewrites when that movement occurred.
     *
     * The fix keeps the column usable exactly as before — NOT NULL with a
     * DEFAULT so an insert that omits it still gets "now" — and drops only the
     * ON UPDATE clause, because a value that means "when this happened" must
     * never change after it is written.
     *
     * Values are preserved: the conversion runs in the connection's own session
     * time zone, which is the same zone the application writes these naive
     * timestamps in, so the stored local time is unchanged.
     *
     * SQLite (and PostgreSQL, which has no implicit ON UPDATE) are untouched.
     */
    private const COLUMNS = [
        'cashier_shifts' => ['opened_at'],
        'inventory_movements' => ['occurred_at'],
    ];

    public function up(): void
    {
        $this->rewriteImplicitOnUpdateColumns(keepDefault: true);
    }

    public function down(): void
    {
        // The up-state is the correct one; this only restores the schema shape
        // the migration found, for a clean rollback.
        $this->rewriteImplicitOnUpdateColumns(keepDefault: true, addOnUpdate: true);
    }

    /**
     * @param  bool  $addOnUpdate  true restores the server-default ON UPDATE clause
     */
    private function rewriteImplicitOnUpdateColumns(bool $keepDefault, bool $addOnUpdate = false): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $meta = DB::selectOne(
                    'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                       FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );

                if ($meta === null) {
                    continue;
                }

                $hasOnUpdate = stripos((string) $meta->EXTRA, 'on update current_timestamp') !== false;

                // Already correct and we are not restoring the bug: nothing to do.
                if (! $hasOnUpdate && ! $addOnUpdate) {
                    continue;
                }

                $definition = 'DATETIME ' . ($meta->IS_NULLABLE === 'NO' ? 'NOT NULL' : 'NULL');

                $hasCurrentDefault = $meta->COLUMN_DEFAULT !== null
                    && stripos((string) $meta->COLUMN_DEFAULT, 'current_timestamp') !== false;

                if ($keepDefault && $hasCurrentDefault) {
                    $definition .= ' DEFAULT CURRENT_TIMESTAMP';
                }

                if ($addOnUpdate) {
                    $definition .= ' ON UPDATE CURRENT_TIMESTAMP';
                }

                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
            }
        }
    }
};
