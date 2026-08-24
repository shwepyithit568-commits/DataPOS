<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Printer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PrinterService
{
    /**
     * Ensure a default 80mm POS receipt printer exists for the store.
     */
    public function ensureDefaultPrinter(Store $store): void
    {
        $count = Printer::where('store_id', $store->id)->count();
        if ($count > 0) {
            return;
        }

        Printer::create([
            'store_id' => $store->id,
            'name' => 'Main POS Counter (80mm)',
            'connection_type' => 'browser',
            'paper_width' => '80mm',
            'ip_address' => null,
            'port' => 9100,
            'device_path' => null,
            'printer_role' => 'receipt',
            'print_copies' => 1,
            'auto_cut' => true,
            'cash_drawer_kick' => true,
            'beep_on_print' => false,
            'print_logo' => true,
            'feed_lines' => 2,
            'header_text' => 'Thank you for shopping with us!',
            'footer_text' => 'Goods once sold are not returnable without receipt.',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Get all printers for the store.
     *
     * @return Collection<int, Printer>
     */
    public function getPrinters(Store $store): Collection
    {
        $this->ensureDefaultPrinter($store);

        return Printer::where('store_id', $store->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get printer summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Store $store): array
    {
        $this->ensureDefaultPrinter($store);

        $printers = Printer::where('store_id', $store->id)->get();
        $defaultPrinter = $printers->firstWhere('is_default', true);

        return [
            'total_printers' => $printers->count(),
            'active_printers' => $printers->where('is_active', true)->count(),
            'network_printers' => $printers->where('connection_type', 'network')->count(),
            'bluetooth_printers' => $printers->where('connection_type', 'bluetooth')->count(),
            'default_printer_name' => $defaultPrinter?->name ?? 'None',
            'default_printer_type' => $defaultPrinter ? strtoupper($defaultPrinter->connection_type) . ' (' . $defaultPrinter->paper_width . ')' : '-',
        ];
    }

    /**
     * Save (create or update) a printer.
     */
    public function savePrinter(Store $store, array $data, ?Printer $printer = null, ?User $user = null): Printer
    {
        return DB::transaction(function () use ($store, $data, $printer, $user) {
            $isDefault = !empty($data['is_default']);

            // If store has 0 other printers, enforce is_default = true
            $otherCount = Printer::where('store_id', $store->id)
                ->when($printer, fn($q) => $q->where('id', '!=', $printer->id))
                ->count();

            if ($otherCount === 0) {
                $isDefault = true;
            }

            if ($isDefault) {
                // Clear is_default for all other printers in store
                Printer::where('store_id', $store->id)->update(['is_default' => false]);
            }

            $attributes = [
                'name' => $data['name'],
                'connection_type' => $data['connection_type'] ?? 'browser',
                'paper_width' => $data['paper_width'] ?? '80mm',
                'ip_address' => $data['ip_address'] ?? null,
                'port' => !empty($data['port']) ? (int) $data['port'] : 9100,
                'device_path' => $data['device_path'] ?? null,
                'printer_role' => $data['printer_role'] ?? 'receipt',
                'print_copies' => max(1, min(5, (int) ($data['print_copies'] ?? 1))),
                'auto_cut' => !empty($data['auto_cut']),
                'cash_drawer_kick' => !empty($data['cash_drawer_kick']),
                'beep_on_print' => !empty($data['beep_on_print']),
                'print_logo' => !empty($data['print_logo']),
                'feed_lines' => max(0, min(10, (int) ($data['feed_lines'] ?? 2))),
                'header_text' => $data['header_text'] ?? null,
                'footer_text' => $data['footer_text'] ?? null,
                'is_default' => $isDefault,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($printer) {
                $printer->update($attributes);
                $action = 'printer_updated';
            } else {
                $printer = Printer::create(array_merge($attributes, ['store_id' => $store->id]));
                $action = 'printer_created';
            }

            AuditLog::write(
                $store->id,
                $action,
                'printers',
                $printer->id,
                [
                    'name' => $printer->name,
                    'connection_type' => $printer->connection_type,
                    'paper_width' => $printer->paper_width,
                    'is_default' => $printer->is_default,
                ],
                $user?->id
            );

            return $printer;
        });
    }

    /**
     * Set a printer as default.
     */
    public function setDefault(Store $store, Printer $printer, ?User $user = null): void
    {
        DB::transaction(function () use ($store, $printer, $user) {
            Printer::where('store_id', $store->id)->update(['is_default' => false]);
            $printer->update(['is_default' => true, 'is_active' => true]);

            AuditLog::write(
                $store->id,
                'printer_set_default',
                'printers',
                $printer->id,
                ['name' => $printer->name],
                $user?->id
            );
        });
    }

    /**
     * Delete a printer.
     */
    public function deletePrinter(Store $store, Printer $printer, ?User $user = null): bool
    {
        return DB::transaction(function () use ($store, $printer, $user) {
            $wasDefault = $printer->is_default;
            $printer->delete();

            if ($wasDefault) {
                // Promote another active printer if available
                $next = Printer::where('store_id', $store->id)->where('is_active', true)->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }

            AuditLog::write(
                $store->id,
                'printer_deleted',
                'printers',
                $printer->id,
                ['name' => $printer->name],
                $user?->id
            );

            return true;
        });
    }
}
