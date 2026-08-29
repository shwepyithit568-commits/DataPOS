<?php

namespace App\Console\Commands;

use Database\Seeders\DemoStoresSeeder;
use Illuminate\Console\Command;

class BuildDemoStoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datapos:build-demo-stores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean legacy store records and build fresh, production-realistic Myanmar SME demo stores (Agriculture, Mobile, CCTV, Pharmacy, Restaurant) with complete sample products, orders, debts and credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Initializing Myanmar SME Demo Stores Builder...');

        $seeder = new DemoStoresSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('===============================================================');
        $this->info('  🎉 DEMO STORES & LOGIN CREDENTIALS CHEATSHEET');
        $this->info('===============================================================');
        $this->line('  👑 Super Admin      : 09777000111 | password (U Aung Myo)');
        $this->line('  👔 Store Manager    : 09111222333 | password (U Kyaw Kyaw)');
        $this->line('  💵 Cashier          : 09222333444 | password (Daw Hla Hla - PIN: 1234)');
        $this->line('  🔧 Technician/Staff : 09333444555 | password (Ko Min Min - PIN: 1234)');
        $this->line('  🏬 Wholesale Partner: 09988776655 | password (U Ba Thein)');
        $this->line('  🛒 Retail Customer  : 09776655443 | password (Daw Nilar)');
        $this->newLine();
        $this->info('  🏬 DEMO STORE URLS:');
        $this->line('  1. Diamond Stone Agri : http://127.0.0.1:8501/store/diamond-stone-agri');
        $this->line('  2. DataPOS Mobile     : http://127.0.0.1:8501/store/datapos-mobile');
        $this->line('  3. ProTech CCTV & PC  : http://127.0.0.1:8501/store/cctv-network-computer');
        $this->line('  4. Shwe Pyi Thit Mob  : http://127.0.0.1:8501/store/mobile-sale-service');
        $this->line('  5. Shwe Mingalar Pharm: http://127.0.0.1:8501/store/pharmacy');
        $this->line('  6. Si Taw Gyi Food Bar: http://127.0.0.1:8501/store/si-taw-gyi-food-bar');
        $this->info('===============================================================');

        return self::SUCCESS;
    }
}
