<?php

namespace App\Console\Commands\Test;


use App\Helpers\_Lionking;
use App\Modules\_SportsbookController;
use Illuminate\Console\Command;
use SimpleXMLElement;

class ClearTestAcc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:ClearTestAcc';

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
        $members = \App\Models\Member::get();
        foreach($members as $member){
            $member->update(['username' => $member->username . "_old",'phone' => "0"]);
        }
        return Command::SUCCESS;
    }

}