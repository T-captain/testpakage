<?php

namespace App\Console\Commands\Data;

use App\Models\BetLog;
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
use App\Helpers\Affiliate;
use App\Http\Controllers\Controller;
use App\Models\Link;

class DbEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CallToDB:edit {table?} {where?} {name?} {column?} {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit data from the database';

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
        // Get the 'Starmaster' user and code
        $starManager = Member::where('username', 'Starmaster')->first();

        if ($starManager) {
            // Get the upline based on the 'Starmaster' code
            $upline = Affiliate::find_upline($starManager->code);

            // Increment the registration count for the 'Starmaster' code
            Link::incrementRegister($starManager->code);

            // Now update the members
            Member::where('id', '>', 2999)
            ->where('id', '<', 8000)
                ->whereNull('top_agent_id')
                ->whereNull('upline_id')
                ->whereNull('upline_type')
                ->whereNull('agent_id')
                ->where('type',1)
                ->update([
                    'top_agent_id' => $upline ? $upline->top_agent_id : null,
                    'upline_type' => $upline ? get_class($upline) : null,
                    'upline_id' => $upline ? $upline->id : null,
                    'agent_id' => $upline ? $upline->id : null,
                    'remark' => 'move under Starmaster'
                ]);
        }


        // $table = $this->argument('table') ?? 'members';
        // $where = $this->argument('where') ?? 'username';
        // $name = $this->argument('name') ?? 'supertest';
        // $column = $this->argument('column') ?? 'product_id';
        // $id = $this->argument('id') ?? null;

        // DB::table($table)->where($where, $name)->update([$column => $id]);

        // $this->info($table . ' with ' . $where . ' ' . $name . ' in column ' . $column . ' have been updated to ' . $id);

        // $betLogs = BetLog::where('product', 'SXYB')->get();

        // foreach ($betLogs as $betLog) {
        //     $this->info("ID: " . $betLog->id . ", Bet ID: " . $betLog->bet_id . ", Product: " . $betLog->product . ", Name: " . $betLog->game);

        //     BetLog::where('id', $betLog->id)->update(['product' => 'SEXYBCRT']);

        //     $updatedBetLog = BetLog::find($betLog->id);
        //     if ($updatedBetLog->product === null || $updatedBetLog->product === '') {
        //         $this->info("Game is still NULL or empty after update.");
        //     } else {
        //         $this->info("Updated Name: " . $updatedBetLog->product);
        //     }
        // }

        return 0;
    }
}
