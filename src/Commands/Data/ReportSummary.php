<?php

namespace App\Console\Commands\Data;

use App\Models\Promotion;
use App\Models\SummaryReport;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:report_summary {date?}';

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

        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $start_of_datetime = $date->copy()->startOfDay();
        $end_of_datetime = $date->copy()->endOfDay();

        $currency = 'MYR';

        $report = DB::query()->select(
            DB::raw("'" . $date->format('Y-m-d') . "' as date"),
            DB::raw("COALESCE((SELECT COUNT(`id`) FROM `members` WHERE `created_at` >= '" . $start_of_datetime . "' AND `created_at` <= '" . $end_of_datetime . "' AND `currency` = '" . $currency . "'), 0) as total_register"),
            DB::raw("COALESCE((SELECT SUM(`product_reports`.`turnover`) FROM `product_reports` WHERE `product_reports`.`date` >= '" . $start_of_datetime . "' AND `product_reports`.`date` <= '" . $end_of_datetime . "'), 0) AS turnover")
        );

        $transactions = DB::table('transactions');
        $transactions->select(
            DB::raw("SUM(`transactions`.`isFirstDeposit`) as first_deposit"),

            // DEPOSIT without Gateway
            DB::raw("SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND NOT EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) THEN 1 ELSE 0 END) as deposit_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND NOT EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', 1, 0)) as deposit_success"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND NOT EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', `transactions`.`amount`, 0)) as deposit_success_amount"),

            // DEPOSIT with Gateway
            DB::raw("SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) THEN 1 ELSE 0 END) as gateway_deposit_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', 1, 0)) as gateway_deposit_success"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND EXISTS (SELECT 1 FROM gateways WHERE gateways.transaction_id = `transactions`.`id`) AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "', `transactions`.`amount`, 0)) as gateway_deposit_success_amount"),

            // Withdrawal
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "',1,0)) as withdrawal_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "'  AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "',1,0)) as withdrawal_success"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "'  AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "',`transactions`.`amount`,0)) as withdrawal_success_amount"),

            // ADJUSTMENT
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_ADJUSTMENT . "',1,0)) as adjustment_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_ADJUSTMENT . "'  AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "',1,0)) as adjustment_success_total"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_ADJUSTMENT . "'  AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "',`transactions`.`amount`,0)) as adjustment_success_amount"),

            // GROSS BALANCE
            DB::raw("SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN `transactions`.`amount` WHEN `transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN -`transactions`.`amount` ELSE 0 END) as gross_balance"),
            // RATE
            DB::raw("(CASE WHEN SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN `transactions`.`amount` WHEN `transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN -`transactions`.`amount` ELSE 0 END) != 0 THEN (SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN `transactions`.`amount` WHEN `transactions`.`type` = '" . Transaction::TYPE_WITHDRAWAL . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN -`transactions`.`amount` ELSE 0 END) / NULLIF(SUM(CASE WHEN `transactions`.`type` = '" . Transaction::TYPE_DEPOSIT . "' AND `transactions`.`status` = '" . Transaction::STATUS_SUCCESS . "' THEN `transactions`.`amount` ELSE 0 END), 0)) * 100 ELSE 0 END) as rate"),

            // free bonus
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_BONUS . "' AND EXISTS (SELECT 1 FROM promotions WHERE promotions.id = `transactions`.`promotion_id` AND promotions.from = '" . Promotion::FROM_REGISTER . "'), `transactions`.`amount`, 0)) as free_bonus"),

            // deposit bonus
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_BONUS . "' AND EXISTS (SELECT 1 FROM promotions WHERE promotions.id = `transactions`.`promotion_id` AND promotions.from = '" . Promotion::FROM_DEPOSIT . "'), `transactions`.`amount`, 0)) as deposit_bonus"),
            DB::raw("SUM(IF(`transactions`.`type` = '" . Transaction::TYPE_REBATE . "',`transactions`.`amount`,0)) as rebate"),
        );

        $transactions->leftJoin('members', 'members.id', '=', 'transactions.member_id');
        $transactions->where('members.currency', $currency);

        $transactions->where('transactions.created_at', '>=', $start_of_datetime);
        $transactions->where('transactions.created_at', '<=', $end_of_datetime);
        $transactions->groupBy(DB::raw("DATE(`transactions`.`created_at`)"));

        $reports = array_merge((array) $report->first(), (array) $transactions->first());


        SummaryReport::updateOrCreate(
            ['date' => $date->format('Y-m-d')],
            [
                'currency' => $currency,
                'total_register' => $reports['total_register'] ?? 0,
                'first_deposit' => $reports['first_deposit'] ?? 0,
                'deposit_total' => $reports['deposit_total'] ?? 0,
                'deposit_success' => $reports['deposit_success'] ?? 0,
                'deposit_success_amount' => $reports['deposit_success_amount'] ?? 0,
                'gateway_deposit_total' => $reports['gateway_deposit_total'] ?? 0,
                'gateway_deposit_success' => $reports['gateway_deposit_success'] ?? 0,
                'gateway_deposit_success_amount' => $reports['gateway_deposit_success_amount'] ?? 0,
                'withdrawal_total' => $reports['withdrawal_total'] ?? 0,
                'withdrawal_success' => $reports['withdrawal_success'] ?? 0,
                'withdrawal_success_amount' => $reports['withdrawal_success_amount'] ?? 0,
                'adjustment_total' => $reports['adjustment_total'] ?? 0,
                'adjustment_success_total' => $reports['adjustment_success_total'] ?? 0,
                'adjustment_success_amount' => $reports['adjustment_success_amount'] ?? 0,
                'gross_balance' => $reports['gross_balance'] ?? 0,
                'rate' => $reports['rate'] ?? 0,
                'deposit_bonus' => $reports['deposit_bonus'] ?? 0,
                'free_bonus' => $reports['free_bonus'] ?? 0,
                'rebate' => $reports['rebate'] ?? 0,
                'turnover' => $reports['turnover'] ?? 0,
            ]
        );


        return Command::SUCCESS;
    }
}
