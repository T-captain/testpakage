<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\MemberAffiliateReport;
use App\Models\MemberSettlement as ModelsMemberSettlement;
use App\Models\memberTopAgentList;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberSettlement extends Command
{
    public $date;
    protected $reports = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:weekly_member_settlement {date?}';

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
        $this->date = $this->argument('date') ?: now()->format('Y-m-d');

        $startOfWeek = Carbon::createFromFormat('Y-m-d', $this->date)
            ->subWeek()
            ->startOfWeek()
            ->addDay();

        $endOfWeek = (clone $startOfWeek)->endOfWeek()->addDay();

        $memberIds = memberTopAgentList::pluck('member_id');

        DB::beginTransaction();

        try {
            $affiliateReportSummary = MemberAffiliateReport::select(
                'member_id',
                DB::raw('SUM(winlose + bonus + rebate + commission + transaction_fee + loyalty_fee + turnover_fee) as total_winlose')
            )
                ->whereIn('member_id', $memberIds)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->groupBy('member_id')
                ->get();


            foreach ($memberIds as $memberId) {
                $report = $affiliateReportSummary->where('member_id', $memberId)->first();
                if ($report && $report->total_winlose != null && $report->total_winlose != '' && $report->total_winlose != 0) {
                    ModelsMemberSettlement::updateOrCreate(
                        [
                            'member_id' => $memberId,
                            'date' => $this->date,
                        ],
                        [
                            'total_winlose' => $report ? $report->total_winlose : 0,
                            'settlement_amount' => 0,
                            'status' => ModelsMemberSettlement::STATUS_PENDING,
                        ]
                    );
                }
            }

            DB::commit();
            $this->info($startOfWeek);
            $this->info($endOfWeek);
            $this->info('Weekly member settlement has been successfully updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during the settlement process: ' . $e->getMessage());
        }

        Helpers::sendNotification("end weekly member settlement");
        return 0;
    }
}
