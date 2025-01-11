<?php

namespace App\Console\Commands\Data;

use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MemberBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_balance';

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
        $members = Member::where('id', '>=', 0)->take(1000)->get();
        $count = 0;

        foreach ($members as $member) {
            $count++;
            try {
                if ($member->last_login_at && $member->last_login_at->gte(now()->subDay())) {
                    $balance = $member->total_balance();
                    cache()->forever('member_balance_' . $member->id, [
                        'top_agent_id' => $member->top_agent_id,
                        'upline_id' => $member->upline_id,
                        'username' => $member->username,
                        'balance' => $balance,
                        'last_updated' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to update balance for member {$member->id}: {$e->getMessage()}");
                continue;
            }
        }

        echo "Updated balance for {$count} members\n";

        return 0;
    }
}
