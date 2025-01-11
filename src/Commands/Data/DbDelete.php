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

class DbDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CallToDB:delete {table?} {column?} {where?}';

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
        $table = $this->argument('table') ?? null;
        $column = $this->argument('column') ?? null;
        $where = $this->argument('where') ?? null;

        $this->info('Rows of ' . $table . ' before delete: ' . DB::table($table)->count());
        DB::table($table)->where($column, $where)->delete();
        $this->info('Rows of ' . $table . ' after delete: ' . DB::table($table)->count());
        $datas = DB::table($table)
            ->orderBy('id', 'desc')
            ->take(20)
            ->get();

        foreach ($datas as $data) {
            $this->info(var_dump($data));
        }
    }
}
