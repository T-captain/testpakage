<?php

namespace App\Console\Commands\Data;

use App\Models\CurrencyProduct;
use App\Models\Game;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProviderLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Str;

class DbAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CallToDB:add {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add data to the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $product->update(['disclaimer' => null]);
        }
        // $games = [                 

        // ];
    
        // foreach ($games as $gameData) {
        //     $code = $gameData['code'];
            
        //     Game::updateOrInsert(
        //         ['code' => $code],
        //         $gameData 
        //     );
        // }
    
        $this->info('emty11');
    
    }
}
