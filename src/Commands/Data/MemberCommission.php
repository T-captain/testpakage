<?php

namespace App\Console\Commands\Data;

use App\Models\BetLog;
use App\Models\Member;
use App\Models\MemberAccount;
use App\Models\MemberCommission as ModelsMemberCommission;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberCommission extends Command
{
    protected $reports = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_commission';

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
        $start_time = Carbon::now()->subDay()->startOfDay();
        $end_time = Carbon::now()->subDay()->endOfDay();

        if (Setting::get('commission_type', 'turnover') == "turnover") {
            $bet_logs = BetLog::select(
                DB::raw('bet_logs.username'),
                DB::raw('bet_logs.product'),
                DB::raw('bet_logs.category'),
                DB::raw("SUM(`bet_logs`.`valid_stake`) as turnover"),
                DB::raw("SUM(`bet_logs`.`winlose`) as winlose"),
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

                    $commission = Setting::get('commission');
                    $upline = $member->upline;

                    for ($i = 1; $i < count($commission) + 1; $i++) {
                        if ($upline && $upline->type == Member::TYPE_PLAYER && get_class($upline) == Member::class && isset($commission[$i])) {
                            if (!isset($this->reports[$upline->id][$bet_log->category][$member->id])) {
                                $this->reports[$upline->id][$bet_log->category][$member->id] = [
                                    'member' => $member->username,
                                    'category' => $bet_log->category,
                                    'turnover' => 0,
                                    'winlose' => 0,
                                    'rate' => $commission[$i][$bet_log->category] ?? 0,
                                    'amount' => 0,
                                ];
                            }
                            $this->reports[$upline->id][$bet_log->category][$member->id]['turnover'] += $bet_log->turnover;
                            $this->reports[$upline->id][$bet_log->category][$member->id]['winlose'] += $bet_log->winlose;
                            $this->reports[$upline->id][$bet_log->category][$member->id]['amount'] += $bet_log->turnover * ($this->reports[$upline->id][$bet_log->category][$member->id]['rate'] ?? 0 / 100);

                            $upline = $upline->upline;
                        }
                    }
                }
            });

            foreach ($this->reports as $upline_id => $categories) {
                foreach ($categories as $category => $report) {
                    ModelsMemberCommission::firstOrCreate([
                        'member_id' => $upline_id,
                        'category' => $category,
                        'date' => now()->format('Y-m-d'),
                    ], [
                        'report' => $report,
                        'amount' => 0,
                        'status' => ModelsMemberCommission::STATUS_PENDING,
                    ])->calculcateTotal();
                }
            }
        }

        if (Setting::get('commission_type', 'turnover') == "winlose") {
            $transactions = Transaction::select(
                DB::raw("transactions.member_id"),
                DB::raw("IF(`transactions`.`type`='" . Transaction::TYPE_DEPOSIT . "',SUM(`transactions`.`amount`),0) as deposit"),
                DB::raw("IF(`transactions`.`type`='" . Transaction::TYPE_WITHDRAWAL . "',SUM(`transactions`.`amount`),0) as withdrawal"),
                DB::raw("IF(`transactions`.`type`='" . Transaction::TYPE_BONUS . "',SUM(`transactions`.`amount`),0) as bonus"),
            )->with(['member'])
                ->where('transactions.status', Transaction::STATUS_SUCCESS)
                ->whereBetween('transactions.created_at', [$start_time, $end_time])
                ->groupBy('transactions.member_id')
                ->get();

            foreach ($transactions as $transaction) {
                $commission = Setting::get('commission_winlose');
                $upline = $transaction->member->upline;

                for ($i = 1; $i < count($commission) + 1; $i++) {
                    if ($upline && get_class($upline) == Member::class && isset($commission[$i])) {
                        $winlose = $transaction->deposit - $transaction->withdrawal - $transaction->bonus;

                        if (!isset($this->reports[$upline->id][$transaction->member_id])) {
                            $this->reports[$upline->id][$transaction->member_id] = [
                                'member' => $transaction->member->username,
                                'rate' => $commission[$i] ?? 0,
                                'amount' => 0,
                                'winlose' => 0,
                            ];
                        }

                        $this->reports[$upline->id][$transaction->member_id]['winlose'] += $winlose;
                        $this->reports[$upline->id][$transaction->member_id]['amount'] += $winlose * ($commission[$i] ?? 0 / 100);

                        $upline = $upline->upline;
                    }
                }
            }

            foreach ($this->reports as $upline_id => $members) {
                ModelsMemberCommission::firstOrCreate([
                    'member_id' => $upline_id,
                    'date' => now(),
                ], [
                    'report' => $members,
                    'amount' => 0,
                    'status' => ModelsMemberCommission::STATUS_PENDING,
                ])->calculcateTotal();
            }
        }

        return 0;
    }
}
