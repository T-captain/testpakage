<?php

namespace App\Console\Commands\Test;

use Illuminate\Console\Command;

class TestProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:TestProduct';

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
        $product = \App\Models\Product::find(1);
        dd($product->product_currencies);

        return Command::SUCCESS;
    }
}
