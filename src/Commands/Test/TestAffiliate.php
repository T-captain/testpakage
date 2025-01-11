<?php

namespace App\Console\Commands\Test;

use App\Models\Member;
use Illuminate\Console\Command;

class TestAffiliate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test-affiliate';

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
        $member = Member::find(1);

        dd($member->all_downlines->toArray());
        return Command::SUCCESS;
    }
}
