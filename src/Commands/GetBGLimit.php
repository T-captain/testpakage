<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetBGLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetBGLimit';

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
        \App\Helpers\BG::getBetLimitList();
    }
}
