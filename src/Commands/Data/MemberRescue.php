<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\Member;
use App\Models\MemberRescue as ModelsMemberRescue;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Double;

class MemberRescue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_rescue';

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
        $start_time = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end_time = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $members = Member::with(['rank'])->get();

        foreach ($members as $member) {
            $winlose = $member->bet_logs()
                ->where('bet_logs.bet_status', 'SETTLED')
                ->whereBetween('bet_logs.modified_at', [$start_time, $end_time])
                ->sum('winlose');


            $transactions =  $member->transactions()->select(
                DB::raw("SUM(`transactions`.`amount`) as amount"),
            )
                ->where('type', Transaction::TYPE_BONUS)
                ->where('status', Transaction::STATUS_SUCCESS)
                ->whereBetween('created_at', [$start_time, $end_time])
                ->groupBy('transactions.type')->get();

            $bonus = 0;

            if ($winlose > 0) {
                $bonus = $transactions->where('type', Transaction::TYPE_BONUS)->sum('amount');
                $max_rescue = $winlose - $bonus;

                $type = "";
                $settings = $member->rank->settings;
                $setting = null;

                if (isset($settings['gift_rescue_amount']) && $settings['gift_rescue_amount']) {
                    $setting = $settings['gift_rescue_amount'];
                    $setting = explode(":", $setting);
                    $type = "amount";
                }

                if (isset($settings['gift_rescue_percentage']) && $settings['gift_rescue_percentage']) {
                    $setting = $settings['gift_rescue_percentage'];
                    $setting = explode(":", $setting);
                    $type = "percentage";
                }

                if ($setting && $winlose > 0) {
                    $amount = $winlose;
                    if ($type == "amount") {
                        if ($max_rescue > $amount) {
                            $amount = $max_rescue;
                        }
                    }

                    if ($type == "percentage") {
                        $amount = $winlose * ($setting[0] / 100);
                    }

                    if ($amount > $setting[0]) {
                        $amount = $setting[0];
                    }

                    if ($amount > 0) {
                        $data = [
                            'report' => [
                                'winlose' => (float)$winlose,
                                'bonus' => $bonus,
                                'amount' => $amount,
                                'turnover' =>  $amount * $setting[1],
                                'rate' => $setting[1]
                            ],
                            'amount' => $amount,
                            'turnover' => $amount * $setting[1],
                            'status' => ModelsMemberRescue::STATUS_PENDING,
                        ];
                        
                        $member_rescue = ModelsMemberRescue::where('member_id', $member->id)->where('date', now()->format('Y-m-d'))->first();
                        if (!$member_rescue) {
                            ModelsMemberRescue::firstOrCreate(['member_id' => $member->id, 'date' => now()->format('Y-m-d')], $data);
                        } else if ($member_rescue->status == ModelsMemberRescue::STATUS_PENDING) {
                            $member_rescue->update($data);
                        } else {
                            // Do Nothing
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
