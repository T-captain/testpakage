<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\BetLog;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\MemberRebate as ModelsMemberRebate;
use App\Models\Product;
use App\Models\RebateSettings;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberRebate extends Command
{
    protected $reports = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_rebate {date?}';

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
        $date = $this->argument('date') ?? now()->format('Y-m-d');

        $startOfTheDay = Carbon::parse($date)->subDay()->startOfDay();
        $endOfTheDay = Carbon::parse($date)->subDay()->endOfDay();

        //Calcul Rate for total Report
        $rebateSettings = RebateSettings::get(['code', 'player_rate', 'upline_level1_rate', 'upline_level2_rate']);
        $totalRate = [];

        foreach ($rebateSettings as $product) {
            $baseRate = $product->player_rate;
            $upline1Rate = $product->upline_level1_rate;
            $upline2Rate = $product->upline_level2_rate;

            $totalRate[$product->code] = [
                'baseRate' => $baseRate,
                'upline_level1_rate' => number_format($upline1Rate, 2, '.', ''),
                'upline_level2_rate' => number_format($upline2Rate, 2, '.', ''),
                'upline1Rate' => number_format($baseRate + $upline1Rate, 2, '.', ''),
                'upline2Rate' => number_format($baseRate + $upline1Rate + $upline2Rate, 2, '.', ''),
            ];
        }

        $reports = [];
        $memberList = [];
        //TODO:: change to product report
        $bet_logs = BetLog::select(
            DB::raw('bet_logs.username'),
            DB::raw('bet_logs.product'),
            DB::raw("SUM(`bet_logs`.`valid_stake`) as stake"),
            DB::raw("SUM(`bet_logs`.`winlose`) as winlose"),
        )->where('bet_logs.bet_status', 'SETTLED')
            ->whereBetween('bet_logs.round_at', [$startOfTheDay, $endOfTheDay])
            ->groupBy('bet_logs.username', 'bet_logs.product')->chunk(500, function ($bet_logs) use (&$reports, &$memberList, $totalRate) {
                foreach ($bet_logs as $bet_log) {
                $member_account = Cache::remember(
                    'member_account.' . $bet_log->username . "." . $bet_log->product,
                    60 * 60 * 24,
                    function () use ($bet_log) {
                        return MemberAccount::whereHas('product', function ($q) use ($bet_log) {
                            $q->where('code', $bet_log->product);
                        })->where('username', strtolower($bet_log->username))->first();
                    }
                );
                if (!isset($member_account)) {
                    continue;
                }

                $member = $member_account->member;
                if ($member->getSetting('rebate', true) == false) {
                    continue;
                } 

                if (!$member->transactions()->whereIn('type', [
                    Transaction::TYPE_DEPOSIT,
                    Transaction::TYPE_TRANSFER_IN,
                ])->where('status', Transaction::STATUS_SUCCESS)->count()) {
                    $member->member_logs()->create([
                        'action_by' => "System",
                        'text' => "Skip rebate because no yet deposit."
                    ]);
                    continue;
                }

                //Calcul total rebate rate
                $finalRate = $totalRate[$bet_log->product]['baseRate'];
                if ($member->rebate_upline) {
                    $finalRate = $totalRate[$bet_log->product]['upline1Rate'];
                    if ($member->rebate_upline->rebate_upline) {
                        $finalRate = $totalRate[$bet_log->product]['upline2Rate'];
                    };
                };



                // Player
                if (!isset($reports[$member->id][RebateSettings::PLAYER][$bet_log->product])) {
                    $reports[$member->id][RebateSettings::PLAYER][$bet_log->product] = [
                        'member' => $member->username,
                        'valid_player_id' => $member->id,
                        'valid_player_username' => $member->username,
                        'category' => $bet_log->category,
                        'turnover' => 0,
                        'winlose' => 0,
                        'foo' => 0,
                        'rate' => $totalRate[$bet_log->product]['baseRate'],
                        'total_report_rate' => number_format($finalRate, 2, '.', ''),
                        'total_report_commission' => 0,
                    ];
                }
                $reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['turnover'] += $bet_log->stake;
                $reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['winlose'] += $bet_log->winlose;
                $reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['foo'] += $bet_log->stake * ($reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['rate'] / 100);
                $reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['total_report_commission'] += $bet_log->stake * ($reports[$member->id][RebateSettings::PLAYER][$bet_log->product]['total_report_rate'] / 100);
                if (!in_array($member->id, $memberList)) {
                    $memberList[] = $member->id;
                }

                //lvl1 Upline
                if (($member->upline_id != $member->id) && ($member->rebate_upline != null)) {
                    if (!isset($reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product])) {
                        $reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product] = [
                            'member' => $member->rebate_upline->username,
                            'valid_player_id' => $member->id,
                            'valid_player_username' => $member->username,
                            'category' => $bet_log->category,
                            'turnover' => 0,
                            'winlose' => 0,
                            'foo' => 0,
                            'rate' => $totalRate[$bet_log->product]['upline_level1_rate'],
                            'total_report_rate' => "0",
                            'total_report_commission' => 0,
                        ];
                    }
                    $reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product]['turnover'] += $bet_log->stake;
                    $reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product]['winlose'] += $bet_log->winlose;
                    $reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product]['foo'] += ($bet_log->stake * $reports[$member->rebate_upline->id][RebateSettings::UPLINE_LEVEL1][$bet_log->product]['rate'] / 100);
                    if (!in_array($member->rebate_upline->id, $memberList)) {
                        $memberList[] = $member->rebate_upline->id;
                    }

                    //lvl2 Upline
                    $level1Member = Member::where('id', $member->rebate_upline->id)->first();
                    if ($level1Member != null) {
                        $level2Member = Member::where('id', $level1Member->upline_id)->first();
                    }
                    if ($level2Member != null) {
                        if (!isset($reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product])) {
                            $reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product] = [
                                'member' => $level2Member->username,
                                'valid_player_id' => $member->id,
                                'valid_player_username' => $member->username,
                                'category' => $bet_log->category,
                                'turnover' => 0,
                                'winlose' => 0,
                                'foo' => 0,
                                'rate' => $totalRate[$bet_log->product]['upline_level2_rate'],
                                'total_report_rate' => "0",
                                'total_report_commission' => 0,
                            ];
                        }
                        $reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product]['turnover'] += $bet_log->stake;
                        $reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product]['winlose'] += $bet_log->winlose;
                        $reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product]['foo'] += ($bet_log->stake * $reports[$level2Member->id][RebateSettings::UPLINE_LEVEL2][$bet_log->product]['rate'] / 100);
                        if (!in_array($level2Member->id, $memberList)) {
                            $memberList[] = $level2Member->id;
                        }
                    }
                }
            }
        });



        $gameList = Product::get();
        foreach ($memberList as $member) {
            foreach (RebateSettings::LEVEL as $level => $levelnumber) {
                if (!isset($reports[$member][$level])) {
                    continue;
                }
                $amount = 0;
                if (isset($reports[$member][$level])) {
                    foreach ($gameList as $game) {
                        if (isset($reports[$member][$level][$game->code])) {
                            $amount += $reports[$member][$level][$game->code]['foo'];
                        }
                    }
                }
                $amount = bcdiv($amount * 100, 100, 2);
                ModelsMemberRebate::updateOrCreate([
                    'member_id' => $member,
                    'level' => $levelnumber,
                    'date' => $date,
                ], [
                    'report' => json_encode($reports[$member][$level]),
                    'amount' => $amount,
                    'status' => ModelsMemberRebate::STATUS_PENDING,
                ]);
            }
        }
        Helpers::sendNotification('done rebate');
        return Command::SUCCESS;
    }
}
