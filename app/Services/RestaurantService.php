<?php

namespace App\Services;

use App\Models\KitchenOrderTicket;
use App\Models\RestaurantTable;
use App\Models\Store;
use Illuminate\Validation\ValidationException;

class RestaurantService
{
    /**
     * Create and route a new Kitchen Order Ticket (KOT).
     *
     * @param Store $store
     * @param array{
     *     branch_id?: int|null,
     *     table_id?: int|null,
     *     server_user_id?: int|null,
     *     order_type?: string,
     *     items: array<int, array{name: string, qty: float|int, modifiers?: string|null}>,
     *     notes?: string|null
     * } $data
     * @return KitchenOrderTicket
     */
    public function createKot(Store $store, array $data): KitchenOrderTicket
    {
        $todayCount = KitchenOrderTicket::where('store_id', $store->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $ticketNum = 'KOT-' . date('ymd') . '-' . str_pad((string) ($todayCount + 1), 3, '0', STR_PAD_LEFT);

        $kot = KitchenOrderTicket::create([
            'store_id'       => $store->id,
            'branch_id'      => $data['branch_id'] ?? null,
            'table_id'       => $data['table_id'] ?? null,
            'server_user_id' => $data['server_user_id'] ?? auth()->id(),
            'ticket_number'  => $ticketNum,
            'order_type'     => $data['order_type'] ?? 'dine_in',
            'items'          => $data['items'],
            'status'         => 'pending',
            'notes'          => $data['notes'] ?? null,
        ]);

        if (! empty($data['table_id'])) {
            $table = RestaurantTable::where('store_id', $store->id)->find($data['table_id']);
            if ($table && $table->isAvailable()) {
                $this->occupyTable($table, $ticketNum);
            }
        }

        return $kot;
    }

    /**
     * Update preparation status of a KOT.
     */
    public function updateKotStatus(KitchenOrderTicket $kot, string $status): KitchenOrderTicket
    {
        $validStatuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];
        if (! in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages(['status' => "Invalid KOT status: {$status}"]);
        }

        $kot->update(['status' => $status]);
        return $kot;
    }

    /**
     * Mark table as occupied.
     */
    public function occupyTable(RestaurantTable $table, ?string $sessionId = null): RestaurantTable
    {
        $table->update([
            'status'            => 'occupied',
            'active_session_id' => $sessionId ?? ('SES-' . uniqid()),
        ]);

        return $table;
    }

    /**
     * Mark table as available.
     */
    public function releaseTable(RestaurantTable $table): RestaurantTable
    {
        $table->update([
            'status'            => 'available',
            'active_session_id' => null,
        ]);

        return $table;
    }

    /**
     * Calculate equal bill split for dining groups.
     *
     * @param float $totalAmount
     * @param int $splitCount
     * @return array{split_count: int, per_person_amount: float, remainder: float}
     */
    public function calculateSplitBill(float $totalAmount, int $splitCount): array
    {
        if ($splitCount <= 0) {
            $splitCount = 1;
        }

        $perPerson = floor($totalAmount / $splitCount);
        $remainder = $totalAmount - ($perPerson * $splitCount);

        return [
            'total_amount'      => round($totalAmount, 2),
            'split_count'       => $splitCount,
            'per_person_amount' => (float) $perPerson,
            'remainder'         => (float) $remainder,
        ];
    }

    /**
     * Generate ESC/POS kitchen thermal ticket string for kitchen printer.
     */
    public function generateKotEscPos(KitchenOrderTicket $kot): string
    {
        $esc = "\x1B";
        $gs  = "\x1D";
        $cols = 48;

        $out = "";
        $out .= $esc . "@"; // Init printer

        // Center Big Header
        $out .= $esc . "a\x01"; // Center
        $out .= $esc . "!\x30"; // Double width + double height
        $out .= "*** KITCHEN ORDER ***\n";

        $out .= $esc . "!\x00"; // Normal font
        $out .= "Ticket: " . $kot->ticket_number . "\n";
        $tableName = $kot->table ? $kot->table->name . " (" . $kot->table->zone . ")" : strtoupper($kot->order_type);
        $out .= "Table / Destination: " . $tableName . "\n";
        $out .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $out .= str_repeat('-', $cols) . "\n";

        // Left align items with big bold font
        $out .= $esc . "a\x00"; // Left
        $out .= $esc . "!\x10"; // Double height font for kitchen readability

        foreach ($kot->items as $item) {
            $qty = $item['qty'] ?? 1;
            $name = $item['name'] ?? 'Item';
            $out .= "{$qty}x {$name}\n";

            if (! empty($item['modifiers'])) {
                $out .= "   >> NOTE: " . $item['modifiers'] . "\n";
            }
        }

        $out .= $esc . "!\x00"; // Normal font
        $out .= str_repeat('-', $cols) . "\n";

        if ($kot->notes) {
            $out .= "Special Instructions: " . $kot->notes . "\n";
            $out .= str_repeat('-', $cols) . "\n";
        }

        $out .= "\n\n\n";
        $out .= $gs . "V\x41\x00"; // Cut

        return $out;
    }
}
