<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether the account's password was ever chosen by the person themselves.
 *
 * A customer created at the POS counter gets a random placeholder password (it
 * only exists so the row has one) and no way to log in until they claim the
 * account online. Without a marker, "a real password" and "a random
 * placeholder" look identical, so registration could not tell the two apart
 * and happily overwrote the password of an account that already had one — a
 * takeover with nothing but the phone number.
 *
 * Null = never chosen (counter-created). Set by registration and by the staff
 * password reset in the customer directory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'password_set_at')) {
                $table->timestamp('password_set_at')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_set_at')) {
                $table->dropColumn('password_set_at');
            }
        });
    }
};
