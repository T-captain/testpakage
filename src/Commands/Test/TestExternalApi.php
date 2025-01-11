<?php

namespace App\Console\Commands\Test;

use App\Http\Controllers\External\APIController;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestExternalApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:external_api';

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


        // member_info
        $member_info = Http::post('https://external_api.slotgame4u.com/api/member_info', [
            'time' => $time = time(),
            'token' => md5(APIController::KEY . $time),
            'username' => $member->username,
        ]);


        // member_deduct_point
        $member_deduct_point = Http::post('https://external_api.slotgame4u.com/api/member_deduct_point', [
            'time' => $time = time(),
            'token' => md5(APIController::KEY . $time),
            'username' => $member->username,
            'amount' => 100,
            'remark' => "TEST"
        ]);

        dd($member_info->json());
        return Command::SUCCESS;
    }
}
