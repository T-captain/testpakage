<?php

namespace App\Console\Commands\Data;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckMemberDuplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:check_member_duplicate';

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
        foreach (Member::where('category', Member::CATEGORY_NORMAL)->get() as $member) {
            $ip_addresses = DB::table('ip_addresses as a')
                ->select(
                    DB::raw('a.ip_address as ip_address'),
                    DB::raw('(SELECT COUNT(DISTINCT(`b`.`member_id`)) FROM `ip_addresses` as b WHERE `a`.`member_id` != `b`.`member_id` AND `a`.`ip_address` = `b`.`ip_address`) as duplicate_count'),
                    DB::raw('(SELECT `c`.`created_at` FROM `ip_addresses` as c WHERE `a`.`member_id` = `c`.`member_id` AND `a`.`ip_address` = `c`.`ip_address` ORDER BY `c`.`id` DESC LIMIT 1) as created_at'),
                )
                ->where('a.member_id', $member->id)
                ->groupBy('a.ip_address', 'a.member_id')
                ->orderBy('created_at', 'DESC')
                ->get();

            if ($ip_addresses->count()) {
                $member->update(['category' => Member::CATEGORY_DUPLICATE]);
            }
        }
        return Command::SUCCESS;
    }
}
