<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\BetLog;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\MemberAffiliateReport;
use App\Models\MemberAgentLink;
use App\Models\MemberCommission;
use App\Models\MemberPlayerReport;
use App\Models\MemberRebate;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AgentCommission extends Command
{
    public $date;
    public $reports = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:agent_commission {date?}';

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
        $this->date = $this->argument('date');
        if (!$this->date) {
            $this->date = now()->format('Y-m-d');
        }

        $start_time = Carbon::createFromFormat('Y-m-d', $this->date)->subDay()->startOfDay();
        $end_time = Carbon::createFromFormat('Y-m-d', $this->date)->subDay()->endOfDay();

        // Settle Betlogs
        $bet_logs = BetLog::select(
            DB::raw('bet_logs.username'),
            DB::raw('bet_logs.product'),
            DB::raw('bet_logs.category'),
            DB::raw("SUM(`bet_logs`.`stake`) as stake"),
            DB::raw("SUM(`bet_logs`.`payout`) as payout"),
            DB::raw("SUM(`bet_logs`.`valid_stake`) as turnover"),
            DB::raw("SUM(`bet_logs`.`winlose`) as winlose"),
            DB::raw("SUM(`bet_logs`.`progressive_share`) as progressive_share"),
            DB::raw("SUM(`bet_logs`.`jackpot_win`) as jackpot_win"),
        )->where('bet_logs.bet_status', 'SETTLED')
            ->whereBetween('bet_logs.round_at', [$start_time, $end_time])
            ->groupBy('bet_logs.username', 'bet_logs.product', 'bet_logs.category');


        $bet_logs->chunk(200, function ($bet_logs) {
            foreach ($bet_logs as $bet_log) {
                $member_account = Cache::remember(
                    'member_account.' . $bet_log->username . "." . $bet_log->product . "." . $bet_log->category,
                    60 * 60 * 24,
                    function () use ($bet_log) {
                        return MemberAccount::whereHas('product', function ($q) use ($bet_log) {
                            $q->where('code', $bet_log->product)->where('category', $bet_log->category);
                        })->where('username', $bet_log->username)->first();
                    }
                );

                if (!isset($member_account)) {
                    continue;
                }

                $member = $member_account->member;


                if (!isset($this->reports[$member->id])) {
                    $this->reports[$member->id] = [
                        'agent_id' => $member->agent_id,
                        'member_id' => $member->id,
                        'username' => $member->username,
                        'stake' => 0,
                        'payout' => 0,
                        'turnover' => 0,
                        'winlose' => 0,
                        'jackpot' => 0,
                        'bonus' => 0,
                        'rebate' => 0,
                        'commission' => 0,
                        'turnover_fee_rate' => Setting::get('affiliate_turnover_fee', 0),
                        'loyalty_fee_rate' => Setting::get('affiliate_loyalty_fee', 0),
                        'transaction_fee_rate' => Setting::get('affiliate_transaction_fee', 0),

                        'deposit' => 0,
                        'withdrawal' => 0,
                        'transaction_fee' => 0,
                        'loyalty_fee' => 0,
                        'turnover_fee' => 0,
                    ];
                }


                $this->reports[$member->id]['stake'] += $bet_log->stake;
                $this->reports[$member->id]['payout'] += $bet_log->payout;
                $this->reports[$member->id]['turnover'] += $bet_log->turnover;
                $this->reports[$member->id]['winlose'] += $bet_log->winlose;
                $this->reports[$member->id]['jackpot'] += $bet_log->jackpot_win;
                if ($this->reports[$member->id]['loyalty_fee_rate']) {
                    $this->reports[$member->id]['loyalty_fee'] += ($bet_log->stake - $bet_log->payout) * ($this->reports[$member->id]['loyalty_fee_rate'] / 100);
                }
                if ($this->reports[$member->id]['turnover_fee_rate']) {
                    $this->reports[$member->id]['turnover_fee'] += ($bet_log->turnover) * ($this->reports[$member->id]['turnover_fee_rate'] / 100);
                }
            }
        });

        // Settle Deposit / Withdrawal / Bonus

        $transactions = Transaction::select(
            DB::raw('transactions.member_id'),
            DB::raw("SUM(IF(`transactions`.`type`='" . Transaction::TYPE_DEPOSIT . "',(`transactions`.`amount`),0)) as deposit"),
            DB::raw("SUM(IF(`transactions`.`type`='" . Transaction::TYPE_WITHDRAWAL . "',(`transactions`.`amount`),0)) as withdrawal"),
            DB::raw("SUM(IF(`transactions`.`type`='" . Transaction::TYPE_BONUS . "',(`transactions`.`amount`),0)) as bonus"),
        )->with(['member'])
            ->where('status', Transaction::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->groupBy('transactions.member_id');

        $transactions->chunk(200, function ($transactions) {
            foreach ($transactions as $transaction) {
                $member = $transaction->member;

                if (!isset($this->reports[$member->id])) {
                    $this->reports[$member->id] = [
                        'agent_id' => $member->agent_id,
                        'member_id' => $member->id,
                        'username' => $member->username,
                        'stake' => 0,
                        'payout' => 0,
                        'turnover' => 0,
                        'winlose' => 0,
                        'jackpot' => 0,
                        'bonus' => 0,
                        'rebate' => 0,
                        'commission' => 0,
                        'turnover_fee_rate' => Setting::get('affiliate_turnover_fee', 0),
                        'loyalty_fee_rate' => Setting::get('affiliate_loyalty_fee', 0),
                        'transaction_fee_rate' => Setting::get('affiliate_transaction_fee', 0),

                        'deposit' => 0,
                        'withdrawal' => 0,
                        'transaction_fee' => 0,
                        'loyalty_fee' => 0,
                        'turnover_fee' => 0,
                    ];
                }

                $this->reports[$member->id]['deposit'] += $transaction->deposit;
                $this->reports[$member->id]['withdrawal'] += $transaction->withdrawal;
                $this->reports[$member->id]['bonus'] += $transaction->bonus;
                $this->reports[$member->id]['transaction_fee'] += ($transaction->deposit + $transaction->withdrawal) * ($this->reports[$member->id]['transaction_fee_rate'] / 100);

                $this->reports[$member->id]['commission'] += $transaction->commission;
            }
        });

        // Settle Rebate
        foreach (MemberRebate::where('date', $start_time->copy()->addDay()->format('Y-m-d'))->get() as $member_rebate) {
            $member = $member_rebate->member;

            if (!isset($this->reports[$member->id])) {
                $this->reports[$member->id] = [
                    'agent_id' => $member->agent_id,
                    'member_id' => $member->id,
                    'username' => $member->username,
                    'stake' => 0,
                    'payout' => 0,
                    'turnover' => 0,
                    'winlose' => 0,
                    'jackpot' => 0,
                    'bonus' => 0,
                    'rebate' => 0,
                    'commission' => 0,
                    'turnover_fee_rate' => Setting::get('affiliate_turnover_fee', 0),
                    'loyalty_fee_rate' => Setting::get('affiliate_loyalty_fee', 0),
                    'transaction_fee_rate' => Setting::get('affiliate_transaction_fee', 0),

                    'deposit' => 0,
                    'withdrawal' => 0,
                    'transaction_fee' => 0,
                    'loyalty_fee' => 0,
                    'turnover_fee' => 0,
                ];
            }
            
            $reportArray = json_decode($member_rebate->report, true);
            
            foreach($reportArray as $report){
                $this->reports[$member->id]['rebate'] += $report['total_report_commission'];
            }

        }

        // Settle Commission
        foreach (MemberCommission::where('date', $start_time->copy()->addDay()->format('Y-m-d'))->where('amount', '>', 0)->get() as $member_commission) {
            $member = $member_commission->member;

            if (!isset($this->reports[$member->id])) {
                $this->reports[$member->id] = [
                    'agent_id' => $member->agent_id,
                    'member_id' => $member->id,
                    'username' => $member->username,
                    'stake' => 0,
                    'payout' => 0,
                    'turnover' => 0,
                    'winlose' => 0,
                    'jackpot' => 0,
                    'bonus' => 0,
                    'rebate' => 0,
                    'commission' => 0,
                    'turnover_fee_rate' => Setting::get('affiliate_turnover_fee', 0),
                    'loyalty_fee_rate' => Setting::get('affiliate_loyalty_fee', 0),
                    'transaction_fee_rate' => Setting::get('affiliate_transaction_fee', 0),

                    'deposit' => 0,
                    'withdrawal' => 0,
                    'transaction_fee' => 0,
                    'loyalty_fee' => 0,
                    'turnover_fee' => 0,
                ];
            }

            $this->reports[$member->id]['commission'] += $member_commission->amount;
        }

        foreach ($this->reports as $report) {
            $rebate = bcdiv($report['rebate'] * 100, 100, 2);
            $member_player_report = MemberPlayerReport::updateOrCreate([
                'member_id' => $report['member_id'],
                'date' => $this->date,
            ], [
                'stake' => $report['stake'],
                'payout' => $report['payout'],
                'turnover' => $report['turnover'],
                'winlose' => $report['winlose'],
                'jackpot' => $report['jackpot'],
                'bonus' => $report['bonus'],
                'rebate' => $rebate,
                'commission' => $report['commission'],
                'deposit' => $report['deposit'],
                'withdrawal' => $report['withdrawal'],
                'transaction_fee' => $report['transaction_fee'],
                'loyalty_fee' => $report['loyalty_fee'],
                'turnover_fee' => $report['turnover_fee'],
                'turnover_fee_rate' => $report['turnover_fee_rate'],
                'loyalty_fee_rate' => $report['loyalty_fee_rate'],
                'transaction_fee_rate' => $report['transaction_fee_rate'],
            ]);

            $current = Member::find($report['member_id']);

            while ($current) {
                // if ($current->type == Member::TYPE_AGENT) {
                    $member_affiliate_report = MemberAffiliateReport::firstOrCreate([
                        'member_id' => $current->id,
                        'date' => $this->date,
                    ], [
                        'stake' => 0,
                        'payout' => 0,
                        'turnover' => 0,
                        'winlose' => 0,
                        'jackpot' => 0,
                        'bonus' => 0,
                        'rebate' => 0,
                        'commission' => 0,
                        'deposit' => 0,
                        'withdrawal' => 0,
                        'transaction_fee' => 0,
                        'loyalty_fee' => 0,
                        'turnover_fee' => 0,
                    ]);

                    MemberAgentLink::updateOrCreate([
                        'member_affiliate_report_id' => $member_affiliate_report->id,
                        'member_player_report_id' => $member_player_report->id,
                    ], []);
                // }
                $current = $current->upline;
            }
        }

        foreach (MemberAffiliateReport::where('date', $this->date)->get() as $member_affiliate_report) {
            $member_affiliate_report->calculate();
        }

        Helpers::sendNotification('end agent commission');
        return Command::SUCCESS;
    }
}
