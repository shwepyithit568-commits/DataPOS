<?php

namespace App\POS\Console;

use App\POS\Services\InventoryService;
use Illuminate\Console\Command;

class InventoryReconcileCommand extends Command
{
    protected $signature = 'inventory:reconcile
                            {--verify : Compare stored balances with the ledger and report mismatches without writing}';

    protected $description = 'Rebuild (default) or verify (--verify) inventory balances from the movement ledger';

    public function handle(InventoryService $inventory): int
    {
        $movements = \DB::table('inventory_movements')->count();

        if ($this->option('verify')) {
            $result = $inventory->verifyBalances();

            $this->info('Inventory reconciliation — VERIFY (read-only)');
            $this->line("Movements in ledger: {$movements}");
            $this->line("Stored balance rows:  {$result['stored']}");
            $this->line("Computed balance rows: {$result['computed']}");

            if ($result['mismatches'] === []) {
                $this->info('✅ Ledger and balances are consistent.');

                return self::SUCCESS;
            }

            $this->error('❌ ' . count($result['mismatches']) . ' mismatch(es) found:');
            $this->table(
                ['store_id', 'warehouse_id', 'product_id', 'variant_id', 'stored', 'expected'],
                array_map(fn ($m) => [
                    $m['store_id'],
                    $m['warehouse_id'],
                    $m['product_id'],
                    $m['product_variant_id'],
                    $m['stored'],
                    $m['expected'],
                ], array_slice($result['mismatches'], 0, 50))
            );

            return self::FAILURE;
        }

        $written = $inventory->rebuildBalances();
        $this->info("✅ Inventory balances rebuilt from {$movements} movement(s) — {$written} balance row(s) written.");

        return self::SUCCESS;
    }
}
