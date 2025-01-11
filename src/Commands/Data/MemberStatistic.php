<?php

namespace App\Console\Commands\Data;

use App\Helpers\_CommonCache;
use App\Http\Helpers;
use App\Models\Member;
use App\Models\ProductReport;
use App\Models\Statistic;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MemberStatistic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_statistic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Update the "Total Active Player" statistic in the Statistic table
        $currentActiveUser = Member::where('last_login_at', '>=', Carbon::now()->subHour())
            ->where('last_login_at', '<=', Carbon::now())
            ->count();

        Statistic::updateOrCreate(Statistic::NAME[Statistic::TOTAL_ACTIVE_PLAYER], $currentActiveUser, null);

        // Update the "Total Register Player" statistic in the Statistic table
        $newlyRegisterUser = Member::where('created_at', '>=', Carbon::now()->startOfDay())
            ->where('created_at', '<=', Carbon::now()->endOfDay())
            ->count();

        Statistic::updateOrCreate(Statistic::NAME[Statistic::TOTAL_REGISTER_PLAYER], $newlyRegisterUser, null);

        $currentSmsAmount = Statistic::where('name', Statistic::NAME[Statistic::SMS_TOTAL_BALANCE])->first();

        if ($currentSmsAmount && $currentSmsAmount->amount < 3000) {
            Helpers::sendNotification_sms('SMS Balance is running low. Current balance: ' . $currentSmsAmount->amount);
        }

        $this->deposit_statistic();
        $this->game_statistic();
        $this->promotion_statistic();

        return 0;
    }


    public function deposit_statistic()
    {
        // deposit gateway success rate
        $transactions = DB::table('transactions');
        $transactions->select(
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', `transactions`.`amount`, 0)) as deposit_total_amount"),
            DB::raw("SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) THEN 1 ELSE 0 END) as gateway_deposit_total"),
            DB::raw("SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`promotion_id` IS NOT NULL AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN 1 ELSE 0 END) as deposit_with_promotion_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', 1, 0)) as gateway_deposit_success"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND NOT EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', 1, 0)) as deposit_success"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND NOT EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', `transactions`.`amount`, 0)) as deposit_success_amount"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', `transactions`.`amount`, 0)) as gateway_deposit_success_amount"),
        );
        $transactions->where('transactions.created_at', '>=', Carbon::now()->startOfDay());
        $transactions->where('transactions.created_at', '<=', Carbon::now()->endOfDay());
        $transactions->groupBy(DB::raw("DATE(`transactions`.`created_at`)"));
        $total_deposit_with_gateway = $transactions->first()->gateway_deposit_total ?? 0;
        $total_deposit_with_gateway_success = $transactions->first()->gateway_deposit_success ?? 0;

        if ($total_deposit_with_gateway == 0 || $total_deposit_with_gateway_success == 0) {
            $depositGatewaySuccessRate = 0;
        } else {
            $depositGatewaySuccessRate = ($total_deposit_with_gateway_success / $total_deposit_with_gateway) * 100;
        }

        Statistic::updateOrCreate(Statistic::NAME[Statistic::GATEWAY_SUCCESS_RATE], $depositGatewaySuccessRate, null);


        // average deposit amount
        $total_amount_deposit = $transactions->first()->deposit_success_amount ?? 0;
        $total_amount_gateway_deposit = $transactions->first()->gateway_deposit_success_amount ?? 0;
        $total_deposit = $total_amount_deposit + $total_amount_gateway_deposit;
        $total_deposit_count = $total_deposit_with_gateway + $total_deposit_with_gateway_success;
        if ($total_deposit == 0 || $total_deposit_count == 0) {
            $average_deposit_amount = 0;
        } else {
            $average_deposit_amount = round($total_deposit / $total_deposit_count, 2);
        }

        Statistic::updateOrCreate(Statistic::NAME[Statistic::AVERAGE_DEPOSIT_AMOUNT], $average_deposit_amount, null);


        // average deposit per hour
        $total_amount_deposit = $transactions->first()->deposit_total_amount ?? 0;
        //divide by hours
        $total_hours = Carbon::now()->diffInHours(Carbon::now()->startOfDay());
        if ($total_hours == 0 || $total_amount_deposit == 0) {
            $average_deposit_per_hour = 0;
        } else {
            $average_deposit_per_hour = round($total_amount_deposit / $total_hours, 2);
        }
        Statistic::updateOrCreate(Statistic::NAME[Statistic::AVERAGE_DEPOSIT_PER_HOUR], $average_deposit_per_hour, null);


        // deposit with promotion overral percentage
        $total_deposit_with_promotion = $transactions->first()->deposit_with_promotion_total ?? 0;

        if (($total_deposit_with_gateway == 0 && $total_deposit_with_gateway_success == 0) || $total_deposit_with_promotion == 0) {
            $deposit_with_promotion = 0;
        } else {
            $deposit_with_promotion = ($total_deposit_with_promotion / ($total_deposit_with_gateway + $total_deposit_with_gateway_success)) * 100;
        }

        Statistic::updateOrCreate(Statistic::NAME[Statistic::DEPOSIT_WITH_PROMOTION], $deposit_with_promotion, null);

        // average number of deposit per hour
        $total_deposit_count = $total_deposit_with_gateway + $total_deposit_with_gateway_success;
        if ($total_hours == 0 || $total_deposit_count == 0) {
            $average_number_of_deposit_per_hour = 0;
        } else {
            $average_number_of_deposit_per_hour = round($total_deposit_count / $total_hours, 2);
        }

        Statistic::updateOrCreate(Statistic::NAME[Statistic::AVERAGE_NUMBER_OF_DEPOSIT_PER_HOUR], $average_number_of_deposit_per_hour, null);


        // top hour deposit number
        $hourly_deposit_counts = [];
        // Loop through each hour of the day
        for ($i = 0; $i < 24; $i++) {
            $transactions = DB::table('transactions');
            $transactions->select(
                DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', 1, 0)) as deposit_success")
            );
            $transactions->where('transactions.created_at', '>=', Carbon::now()->startOfDay()->addHours($i));
            $transactions->where('transactions.created_at', '<=', Carbon::now()->startOfDay()->addHours($i + 1));
            $total_deposit_success = $transactions->first()->deposit_success ?? 0;

            if ($total_deposit_success == 0) {
                continue;
            }
            // Store the deposit count and corresponding hour
            $hourly_deposit_counts[$i] = [
                'hour' => self::formatHour($i),
                'deposit_count' => $total_deposit_success
            ];
        }

        // Sort the array by deposit count in descending order
        usort($hourly_deposit_counts, function ($a, $b) {
            return $b['deposit_count'] <=> $a['deposit_count'];
        });

        // Get the top 5 hours
        $top_5_hours = array_slice($hourly_deposit_counts, 0, 5);

        Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_HOUR_DEPOSIT_NUMBER], 0, [
            'data' => $top_5_hours
        ]);
    }

    public function game_statistic()
    {
        $mostPlayedGames = ProductReport::where('date', Carbon::now()->format('Y-m-d'))
            ->select('product_id', DB::raw('COUNT(DISTINCT member_id) as member_count'))
            ->groupBy('product_id')
            ->orderBy('member_count', 'desc')
            ->take(5)
            ->get();

        if ($mostPlayedGames) {
            $gameList = [];
            foreach ($mostPlayedGames as $mostPlayedGame) {
                $mostPopularProductId = $mostPlayedGame->product_id;
                $product = _CommonCache::product($mostPopularProductId);
                $memberCount = $mostPlayedGame->member_count;
                $gameList[] = [
                    'product' => $product->name,
                    'member_count' => $memberCount
                ];
            }

            if (count($gameList) > 0) {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_PLAYED], 0, [
                    'data' => $gameList
                ]);
            } else {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_PLAYED], 0, [
                    'data' => null
                ]);
            }
        } else {
            Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_PLAYED], 0, [
                'data' => null
            ]);
        }

        $topAverageBetGames = ProductReport::where('date', Carbon::now()->format('Y-m-d'))
            ->select('product_id', DB::raw('SUM(wager) as total_bet_amount'), DB::raw('SUM(turnover) as total_turnover'))
            ->groupBy('product_id')
            ->orderBy(DB::raw('SUM(turnover) / SUM(wager)'), 'desc')
            ->take(5)
            ->get();

        if ($topAverageBetGames) {
            $gameList = [];
            foreach ($topAverageBetGames as $topAverageBetGame) {
                $mostPopularProductId = $topAverageBetGame->product_id;
                $product = _CommonCache::product($mostPopularProductId);
                $averageBet = round($topAverageBetGame->total_turnover / $topAverageBetGame->total_bet_amount, 2);
                $gameList[] = [
                    'product' => $product->name,
                    'average_bet' => $averageBet
                ];
            }

            if (count($gameList) > 0) {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_AVERAGE_BET], 0, [
                    'data' => $gameList
                ]);
            } else {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_AVERAGE_BET], 0, [
                    'data' => null
                ]);
            }
        } else {
            Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_GAME_AVERAGE_BET], 0, [
                'data' => null
            ]);
        }
    }

    public function promotion_statistic()
    {
        $transactions = Transaction::where('created_at', '>=', Carbon::now()->startOfDay())
            ->where('created_at', '<=', Carbon::now()->endOfDay())
            ->whereNotNull('promotion_id')
            ->select('promotion_id', DB::raw('COUNT(*) as promotion_count'))
            ->groupBy('promotion_id')
            ->orderBy('promotion_count', 'desc')
            ->take(10)
            ->get();

        if ($transactions) {
            $promotions = [];
            foreach ($transactions as $transaction) {
                $promotion = _CommonCache::promotion_item($transaction->promotion_id);
                $promotionCount = $transaction->promotion_count;
                $promotions[] = [
                    'promotion' => $promotion->title,
                    'count' => $promotionCount
                ];
            }

            if (count($promotions) > 0) {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_PROMOTIONS], 0, [
                    'data' => $promotions
                ]);
            } else {
                Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_PROMOTIONS], 0, [
                    'data' => null
                ]);
            }
        } else {
            Statistic::updateOrCreate(Statistic::NAME[Statistic::TOP_PROMOTIONS], 0, [
                'data' => null
            ]);
        }
    }

    function formatHour($hour)
    {
        $time = Carbon::createFromTime($hour);
        return $time->format('ga');
    }
}
