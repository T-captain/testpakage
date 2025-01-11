<?php

namespace App\Console\Commands\Data;

use App\Models\Member;
use App\Models\MemberFollowup;
use App\Models\Setting;
use Illuminate\Console\Command;

class Followup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:followup';

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
        $followup_nr = Setting::get("followup_nr");
        if ($followup_nr > 0) {
            // New Register
            $members = Member::whereDoesntHave('member_followups', function ($q) {
                return $q->where('type', "No Register")
                    ->orWhere('status', MemberFollowup::STATUS_PENDING);
            })->where('created_at', '<=', now()->subSeconds($followup_nr))->get();
            foreach ($members as $member) {
                $member->member_followups()->create([
                    'member_id' => $member->id,
                    'type' => "New Register",
                    'remark' => null,
                    'status' => MemberFollowup::STATUS_PENDING,
                ]);
            }
        }

        $followup_nd = Setting::get("followup_nd");
        if ($followup_nd > 0) {
            // No Deposit
            $members = Member::whereDoesntHave('last_deposit')->whereDoesntHave('member_followups', function ($q) {
                return $q->where('type', "No Deposit")
                    ->orWhere('status', MemberFollowup::STATUS_PENDING);
            })->where('created_at', '<=', now()->subSeconds($followup_nd))->get();

            foreach ($members as $member) {
                $member->member_followups()->create([
                    'member_id' => $member->id,
                    'type' => "No Deposit",
                    'remark' => null,
                    'status' => MemberFollowup::STATUS_PENDING,
                ]);
            }
        }

        $followup_nl = Setting::get("followup_nl");
        if ($followup_nl > 0) {
            // No Login
            $members = Member::whereDoesntHave('member_followups', function ($q) use ($followup_nl) {
                return $q->where('type', 'No Login')
                    ->where('created_at', ">=", now()->subSeconds($followup_nl))
                    ->orWhere('status', MemberFollowup::STATUS_PENDING);
            })->where('last_login_at', '<=', now()->subSeconds($followup_nl))->get();

            foreach ($members as $member) {
                $member->member_followups()->create([
                    'member_id' => $member->id,
                    'type' => "No Login",
                    'remark' => null,
                    'status' => MemberFollowup::STATUS_PENDING,
                ]);
            }
        }

        $followup_np = Setting::get("followup_np");
        if ($followup_np > 0) {
            // No Play
            $members = Member::whereDoesntHave('bet_logs', function ($q) use ($followup_np) {
                return $q->where('round_at', ">=", now()->subSeconds($followup_np));
            })->whereDoesntHave('member_followups', function ($q) use ($followup_np) {
                return $q->where('type', 'No Play')
                ->where('created_at', ">=", now()->subSeconds($followup_np))
                ->orWhere('status', MemberFollowup::STATUS_PENDING);
            })->get();

            foreach ($members as $member) {
                $member->member_followups()->create([
                    'member_id' => $member->id,
                    'type' => "No Play",
                    'remark' => null,
                    'status' => MemberFollowup::STATUS_PENDING,
                ]);
            }
        }
        return 0;
    }
}
