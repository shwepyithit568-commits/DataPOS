<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voucher_templates', function (Blueprint $table) {
            $table->string('document_type', 40)->nullable()->after('paper_size')->index();
        });

        // Seed document_type and user-friendly naming for existing templates
        \Illuminate\Support\Facades\DB::table('voucher_templates')
            ->where('paper_size', '80mm')
            ->whereNull('document_type')
            ->update([
                'document_type' => 'pos_sale',
                'name' => 'POS Invoice (POS အရောင်းပြေစာ)',
            ]);

        \Illuminate\Support\Facades\DB::table('voucher_templates')
            ->where('paper_size', 'a5')
            ->whereNull('document_type')
            ->update([
                'document_type' => 'repair',
                'name' => 'Service Invoice (ဖုန်းပြင်/ဝန်ဆောင်မှု ဘောက်ချာ)',
            ]);

        \Illuminate\Support\Facades\DB::table('voucher_templates')
            ->where('paper_size', 'a4')
            ->whereNull('document_type')
            ->update([
                'document_type' => 'invoice',
                'name' => 'Commercial Tax Invoice (ကုမ္ပဏီသုံး အခွန်ပြေစာ)',
            ]);

        \Illuminate\Support\Facades\DB::table('voucher_templates')
            ->where('paper_size', '58mm')
            ->whereNull('document_type')
            ->update([
                'document_type' => 'mini_slip',
                'name' => 'Mini Slip (လက်ကိုင်ပြေစာအကျဉ်း)',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_templates', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
    }
};
