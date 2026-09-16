<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the cashier-shift uniqueness rule.
     *
     * The old index was unique(store_id, register_name, status). Because every
     * closed shift shares status='closed', that allowed only ONE closed shift
     * per register for the lifetime of the store — the second ever close on
     * "Register 1" raised a UNIQUE violation, breaking daily closing from the
     * second day onwards.
     *
     * The invariant we actually want is "at most one OPEN shift per register".
     * `open_shift_key` holds "<store_id>:<register_name>" while a shift is open
     * and NULL once closed; since NULLs never collide in a unique index, any
     * number of past closings may coexist.
     */
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropUnique('cashier_shifts_open_register_unique');
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->string('open_shift_key', 150)->nullable()->after('register_name');
        });

        // Backfill open shifts. Done in PHP rather than SQL because string
        // concatenation is not portable (`||` is logical OR on MySQL).
        // Duplicates cannot occur: the index dropped above already guaranteed
        // at most one open shift per (store, register).
        DB::table('cashier_shifts')
            ->where('status', 'open')
            ->orderBy('id')
            ->select(['id', 'store_id', 'register_name'])
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('cashier_shifts')
                        ->where('id', $row->id)
                        ->update([
                            'open_shift_key' => $row->store_id . ':' . $row->register_name,
                        ]);
                }
            });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->unique('open_shift_key', 'cashier_shifts_open_shift_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropUnique('cashier_shifts_open_shift_key_unique');
            $table->dropColumn('open_shift_key');
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->unique(['store_id', 'register_name', 'status'], 'cashier_shifts_open_register_unique');
        });
    }
};
