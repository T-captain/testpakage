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

class DbView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CallToDB:view {data?} {row?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View data from the database';

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
        $data = $this->argument('data') ?? 'provider_logs';
        $row = $this->argument('row') ?? '5';
        $this->info(DB::table($data)->count());
        $members = DB::table($data)
        ->orderBy('created_at', 'desc')
            ->take($row)
            ->get();
            
            foreach ($members as $member) {
                $this->info(var_dump($member));
            }
            

        return 0;
    }
}
