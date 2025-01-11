<?php

namespace App\Console\Commands\Test;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Helpers\_Vpower;

class FetchGamelogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test/FetchGamelogs';

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
        $current = now()->subDays(3)->startOfDay();

        while ($current->lte(now())) {
            $this->process_vpower($current);
            $current->addHour();
        }

        return Command::SUCCESS;
    }

    public function process_vpower()
    {
        $startDate = $current->copy()->format('Y-m-d H:i:s');
        $endDate = $current->copy()->addHour()->format('Y-m-d H:i:s');
        echo $startDate . " - " . $endDate;
        $betTickets = _Vpower::getBets($startDate, $endDate);
        echo "\r\n";
        if ($betTickets) {
            \App\Console\Commands\GetBets\_VpowerBets::process($betTickets);
        }
    }
}
