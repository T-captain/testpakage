<?php

namespace App\Console\Commands\Test;

use Illuminate\Console\Command;

class RefetchMonthBets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:RefetchMonthBets';

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
        $current = now()->startOfMonth();

        while ($current->lte(now())) {
            echo $current->format('Y-m-d') . "\r\n";
            \Artisan::call('get_bets:_918kiss ' . $current->format('Y-m-d'));
            \Artisan::call('get_bets:_Pussy888 ' . $current->format('Y-m-d'));
            \Artisan::call('get_bets:_Mega888 ' . $current->format('Y-m-d'));
            $current->addDay();
        }
    }
}
