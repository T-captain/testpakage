<?php

namespace App\Console\Commands\Data;

use App\Models\ClosingBalanceReport;
use App\Models\Payment;
use Illuminate\Console\Command;

class ClosingBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:closing_balance';

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
        foreach (Payment::where('type', Payment::TYPE_ONLINE_TRANSFER)->get() as $payment) {
            $output[] = [
                'payment_id' => $payment->id,
                'bank' => $payment->name,
                'account' => $payment->identify,
                'closing_balance' => floatval(number_format($payment->getBalance(), 2)),
            ];
        }

        foreach($output as $closingData){
            ClosingBalanceReport::updateOrCreate([
                'closing_date' => now()->format('Y-m-d'),
                'payment_id' => $closingData['payment_id'],
            ], $closingData);
        }

        return Command::SUCCESS;
    }
}
