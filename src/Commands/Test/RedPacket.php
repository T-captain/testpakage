<?php

namespace App\Console\Commands\Test;

use App\Models\Member;
use App\Models\RedPacket as ModelsRedPacket;
use Illuminate\Console\Command;

class RedPacket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:RedPacket';

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
        $member = Member::where('username','testing')->first();
        $red_packet = ModelsRedPacket::find(1);
        return Command::SUCCESS;
    }
}
