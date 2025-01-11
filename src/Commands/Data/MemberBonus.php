<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\Member;
use App\Models\MemberBonus as MemberBonusModel;
use App\Models\User;
use Illuminate\Console\Command;

class MemberBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_bonus';

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
        Helpers::sendNotification("Start Member Bonus");
        $member_bonuses = MemberBonusModel::where('status', MemberBonusModel::STATUS_ACTIVE)->get();

        if ($member_bonuses && count($member_bonuses) > 0) {
            foreach ($member_bonuses as $member_bonus) {
                $member_bonus->calculate();
            }
        }

        Helpers::sendNotification("End Member Bonus");
        return 0;
    }
}
