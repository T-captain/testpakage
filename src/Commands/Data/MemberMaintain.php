<?php

namespace App\Console\Commands\Data;

use App\Models\Member;
use Illuminate\Console\Command;

class MemberMaintain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_maintain';

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
        foreach (Member::get() as $member) {
            $member->maintain();
        }
        return Command::SUCCESS;
    }
}
