<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\BetLog;
use App\Models\MemberBonus;
use Illuminate\Console\Command;

class BetLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:bet_logs';

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
        BetLog::where('is_settle', false)
            ->where('bet_status', 'SETTLED')
            ->orderBy('id', 'desc')
            ->chunk(500, function ($betlogs) {
                foreach ($betlogs as $betlog) {
                    $betlog->settle();
                }
            });

        return Command::SUCCESS;
    }
}
