<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\BetLog;
use App\Models\MemberAccount;
use App\Models\ProductReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GenerateReport extends Command
{
    public $outputs = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:generate_report {date?}';

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
        $startOfTheDay = $date->copy()->startOfDay();
        $endOfTheDay = $date->copy()->endOfDay();
        $chunkSize = 500; // Adjust the chunk size as needed

        BetLog::select(
            DB::raw('bet_logs.username'),
            DB::raw('bet_logs.product'),
            DB::raw('bet_logs.category'),
            DB::raw('SUM(bet_logs.stake) as stake'),
            DB::raw('SUM(bet_logs.valid_stake) as valid_stake'),
            DB::raw('SUM(bet_logs.payout) as payout'),
            DB::raw('SUM(bet_logs.winlose) as winlose'),
            DB::raw('SUM(bet_logs.jackpot_win) as jackpot_win'),
            DB::raw('SUM(bet_logs.progressive_share) as progressive_share'),
            DB::raw('COUNT(*) as bet_count'),
        )->where('bet_logs.bet_status', 'SETTLED')
            ->whereBetween('bet_logs.round_at', [$startOfTheDay, $endOfTheDay])
            ->groupBy('bet_logs.username', 'bet_logs.product')
            ->chunk($chunkSize, function ($betLogs) use ($date) {
                foreach ($betLogs as $betLog) {
                    $memberAccount = Cache::remember(
                        'member_account.' . $betLog->username . "." . $betLog->product,
                        60 * 60 * 24,
                        function () use ($betLog) {
                            return MemberAccount::whereHas('product', function ($q) use ($betLog) {
                                $q->where('code', $betLog->product);
                            })->where('username', strtolower($betLog->username))->first();
                        }
                    );

                    if (!$memberAccount) {
                        continue;
                    }

                    $productId = $memberAccount->product_id;
                    $memberId = $memberAccount->member_id;

                    if (!isset($this->outputs[$memberId][$productId])) {
                        $this->outputs[$memberId][$productId] = [
                            'category' => $betLog->category,
                            'wager' => 0,
                            'openbets' => 0,
                            'turnover' => 0,
                            'realbets' => 0,
                            'profit_loss' => 0,
                            'jackpot_win' => 0,
                            'progressive_share' => 0,
                        ];
                    }

                    $this->outputs[$memberId][$productId]['wager'] += $betLog->bet_count;
                    $this->outputs[$memberId][$productId]['turnover'] += $betLog->stake;
                    $this->outputs[$memberId][$productId]['realbets'] += $betLog->valid_stake;
                    $this->outputs[$memberId][$productId]['profit_loss'] += $betLog->winlose;
                    $this->outputs[$memberId][$productId]['jackpot_win'] += $betLog->jackpot_win;
                    $this->outputs[$memberId][$productId]['progressive_share'] += $betLog->progressive_share;
                }

                // Move this outside the loop to avoid unnecessary repetition
                foreach ($this->outputs as $memberId => $report) {
                    foreach ($report as $productId => $data) {
                        ProductReport::updateOrCreate([
                            'date' => $date->format('Y-m-d'),
                            'member_id' => $memberId,
                            'product_id' => $productId,
                        ], $data);
                    }
                }
            });

        return Command::SUCCESS;
    }
}
