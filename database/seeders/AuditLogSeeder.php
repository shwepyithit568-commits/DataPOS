<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $store = DB::table('stores')->where('slug', 'datapos-mobile')->first();
        if (!$store) {
            return;
        }

        $owner = DB::table('users')->where('phone', '09100000001')->first();
        $manager = DB::table('users')->where('phone', '09100000002')->first();
        $staff = DB::table('users')->where('phone', '09100000003')->first();

        // Clear existing for clean reload
        DB::table('audit_logs')->where('store_id', $store->id)->delete();

        $logs = [
            [
                'store_id'    => $store->id,
                'actor_id'    => $owner?->id,
                'action'      => 'bulk_price_updated',
                'entity_type' => 'product',
                'entity_id'   => 1,
                'metadata'    => json_encode([
                    'product_name' => 'Samsung Galaxy S24 Ultra (256GB)',
                    'old_price'    => 3850000,
                    'new_price'    => 3900000,
                    'reason'       => 'USD exchange rate adjustment (+50,000 Ks)',
                    'affected_qty' => 5,
                ]),
                'ip_address'  => '192.168.1.100',
                'created_at'  => $now->copy()->subMinutes(15),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $manager?->id,
                'action'      => 'inventory_adjustment_posted',
                'entity_type' => 'inventory_adjustment',
                'entity_id'   => 101,
                'metadata'    => json_encode([
                    'ref_number' => 'ADJ-20260825-01',
                    'item'       => 'Type-C Fast Charging Cable 65W',
                    'qty_change' => -2,
                    'type'       => 'damage_loss',
                    'reason'     => 'Damaged during showcase demo',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subHours(1),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $staff?->id,
                'action'      => 'pos_receipt_reprinted',
                'entity_type' => 'pos_sale',
                'entity_id'   => 201,
                'metadata'    => json_encode([
                    'voucher_no'      => 'INV-20260825-0042',
                    'customer_name'   => 'Walk-in Customer',
                    'total_amount'    => 45000,
                    'reprint_reason'  => 'Customer requested duplicate slip for warranty claim',
                    'original_printed'=> $now->copy()->subHours(3)->toDateTimeString(),
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subHours(2),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $manager?->id,
                'action'      => 'financial_transaction_approved',
                'entity_type' => 'financial_transaction',
                'entity_id'   => 501,
                'metadata'    => json_encode([
                    'tx_type'     => 'cash_withdrawal',
                    'amount'      => 150000,
                    'account'     => 'Main Cash Drawer',
                    'category'    => 'Store Utility & Refreshments',
                    'approved_by' => 'Daw Mya (Manager)',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subHours(4),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $manager?->id,
                'action'      => 'daily_closing_approved',
                'entity_type' => 'daily_closing',
                'entity_id'   => 301,
                'metadata'    => json_encode([
                    'closing_date'      => $now->copy()->subDay()->toDateString(),
                    'system_total'      => 1850000,
                    'actual_cash_count' => 1850000,
                    'discrepancy'       => 0,
                    'status'            => 'balanced',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subDay()->setHour(21)->setMinute(30),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $owner?->id,
                'action'      => 'staff_role_assigned',
                'entity_type' => 'user',
                'entity_id'   => $staff?->id,
                'metadata'    => json_encode([
                    'target_user' => 'Ko Kyaw (Staff)',
                    'role_name'   => 'Cashier & Sales Rep',
                    'permissions' => ['pos_sale', 'view_catalog', 'issue_receipt'],
                    'assigned_by' => 'U Ba (Platform Owner)',
                ]),
                'ip_address'  => '192.168.1.100',
                'created_at'  => $now->copy()->subDays(2),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => $staff?->id,
                'action'      => 'customer_debt_collected',
                'entity_type' => 'customer_debt',
                'entity_id'   => 401,
                'metadata'    => json_encode([
                    'customer_name'   => 'U Tun (Regular Wholesale)',
                    'amount_collected'=> 250000,
                    'payment_method'  => 'KPay Transfer',
                    'remaining_debt'  => 50000,
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subDays(3),
            ],
            [
                'store_id'    => $store->id,
                'actor_id'    => null,
                'action'      => 'pos_pin_failed',
                'entity_type' => 'pos_terminal',
                'entity_id'   => 1,
                'metadata'    => json_encode([
                    'terminal'    => 'POS-Counter-01',
                    'attempt'     => 'Manager PIN required for 15% discount',
                    'result'      => 'Invalid PIN entered',
                    'throttled'   => false,
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subDays(4),
            ],
        ];

        DB::table('audit_logs')->insert($logs);
    }
}
