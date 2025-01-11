<?php

namespace App\Console\Commands\Data;

use App\Models\MemberGameReport as ModelsMemberGameReport;
use App\Models\ProductReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MemberGameReport extends Command
{
    public $outputs = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_game_report {date?}';

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
        $date = $date->copy()->format('Y-m-d');
        $chunkSize = 500;

        //bet log
        ProductReport::select(
            DB::raw('product_reports.member_id'),
            DB::raw('product_reports.product_id'),
            DB::raw('SUM(product_reports.turnover) as stake'),
            DB::raw('SUM(product_reports.realbets) as valid_stake'),
            // DB::raw('SUM(product_reports.payout) as payout'),
            DB::raw('SUM(product_reports.profit_loss) *-1 as profit_loss'),
            // DB::raw('SUM(product_reports.expenses) as expenses'),
        )->where('product_reports.date', $date)
            ->groupBy('product_reports.member_id', 'product_reports.product_id')
            ->chunk($chunkSize, function ($data) {
                foreach ($data as $item) {

                    $top_agent_id = $item->member->top_agent_id ?? null;
                    $upline_id = $item->member->upline_id ?? null;
                    $member_id = $item->member->id ?? null;
                    $product_id = $item->product_id;
                    $category = $item->product->category;

                    if (!isset($this->outputs[$top_agent_id][$product_id])) {
                        $this->outputs[$top_agent_id][$product_id] = [
                            'member_id' => $member_id,
                            'upline_id' => $upline_id,
                            'category' => $category,
                            'stake' => 0,
                            'valid_stake' => 0,
                            'payout' => 0,
                            'profit_loss' => 0,
                            'expenses' => 0,
                            'profit_share' => 0,
                        ];
                    }

                    $this->outputs[$top_agent_id][$product_id]['stake'] += $item->stake;
                    $this->outputs[$top_agent_id][$product_id]['valid_stake'] += $item->valid_stake;
                    // $this->outputs[$top_agent_id][$product_id]['payout'] += $item->payout;
                    $this->outputs[$top_agent_id][$product_id]['profit_loss'] += $item->profit_loss;
                }
            });

        foreach ($this->outputs as $top_agent_id => $report) {
            foreach ($report as $product_id => $data) {
                ModelsMemberGameReport::updateOrCreate(
                    [
                        'top_agent_id' => $top_agent_id,
                        'date' => $date,
                        'product_id' => $product_id,
                    ],
                    $data
                );
            }
        }

        return Command::SUCCESS;
    }
}
