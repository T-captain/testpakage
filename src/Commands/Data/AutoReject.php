<?php

namespace App\Console\Commands\Data;

use App\Models\BetLog;
use App\Models\MemberBonus;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoReject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:auto_reject';

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
        Transaction::whereHas('payment', function ($q) {
            return $q->whereIn('type', Payment::getPaymentGatewayType());;
        })->where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', Transaction::STATUS_PENDING)
            ->where('created_at', '<=', Carbon::now()->subHours(2))
            ->update([
                'action_by' => "SYSTEM",
                'action_at' => now(),
                'status' => Transaction::STATUS_FAIL,
                'remark' => "Rejected (AR)",
            ]);

        return Command::SUCCESS;
    }
}
