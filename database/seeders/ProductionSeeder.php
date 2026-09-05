<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed only production-required records.
     *
     * Seeds essential standard defaults for active stores (Blog content, standard
     * SME Expense Categories, How-To-Order default guides).
     *
     * Guaranteed safe for production: does NOT create demo users, products, or fake sales.
     */
    public function run(): void
    {
        $this->call([
            RefreshDataPOSBlogContentSeeder::class,
            ExpenseCategorySeeder::class,
            HowToOrderContentSeeder::class,
        ]);

        $this->command?->info('ProductionSeeder completed: production blog, expense categories & order guides seeded.');
    }
}
