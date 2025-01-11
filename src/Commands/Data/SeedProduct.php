<?php

namespace App\Console\Commands\Data;

use Database\Seeders\ProductSeeder;
use Illuminate\Console\Command;

class SeedProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:seed_product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $productseeder = new ProductSeeder();
        $productseeder->run();

        return Command::SUCCESS;
    }
}
