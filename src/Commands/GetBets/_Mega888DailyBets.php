<?php

namespace App\Console\Commands\GetBets;

use App\Jobs\ProcessMegaHourlyBetLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class _Mega888DailyBets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get_bets:_Mega888DailyBets {date?}';

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
        $startDate = $date->copy()->startOfDay()->addHour()->format('Y-m-d H:i:s');
        $endDate = $date->copy()->endOfDay()->format('Y-m-d H:i:s');
        $delay = 0;

        $currentHour = Carbon::parse($startDate);
        while ($currentHour->lessThanOrEqualTo(Carbon::parse($endDate))) {
            $nextHour = $currentHour->copy()->addHour();
            ProcessMegaHourlyBetLog::dispatch($currentHour)->delay($delay);
            $currentHour = $nextHour;
            $delay += 21;
        }

        return 0;
    }
}
