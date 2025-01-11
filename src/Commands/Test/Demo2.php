<?php

namespace App\Console\Commands\Test;

use App\Helpers\_918kiss;
use App\Helpers\_AWC;
use App\Helpers\_Jili;
use App\Helpers\_PP;
use App\Helpers\_Sexybrct;
use App\Helpers\_Vpower;
use App\Helpers\Pussy888;
use App\Http\Helpers;
use App\Jobs\ProcessPussyInsertBetLog;
use App\Jobs\ProcessTransaction;
use App\Models\Product;
use Illuminate\Console\Command;
use App\Models\BetLog;
use App\Models\MemberAccount;
use App\Models\Transaction;
use App\Modules\_AWCController;
use App\Modules\_Pussy888Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class Demo2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:Demo2 {date?}';

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
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now()->copy();
        $date = $date->format('Y-m-d H:i:s');


        $transaction = Transaction::find(34144);
        ProcessTransaction::dispatch($transaction, true)->onQueue('transactions');

        return 0;
    }
}
