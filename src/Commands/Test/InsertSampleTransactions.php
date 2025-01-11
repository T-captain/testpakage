<?php

namespace App\Console\Commands\Test;

use App\Jobs\ProcessTransaction;
use App\Models\Member;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InsertSampleTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:insert_sample_transactions';

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
        $member = Member::find(1);
        $member->update(['balance' => 0]);

        // // Success Deposit
        // echo "In Progress to Pending -> Member Balance : $member->balance , Estimate Balance : 0 \r\n";
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_DEPOSIT,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);
        // ProcessTransaction::dispatch($transaction, true);
        // $member = Member::find(1);
        // echo "Pending to Success -> Member Balance : $member->balance , Estimate Balance :100 \r\n";

        // // Reject Deposit
        // echo "In Progress to Pending -> Member Balance : $member->balance , Estimate Balance : 100 \r\n";
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_DEPOSIT,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);
        // ProcessTransaction::dispatch($transaction, false);
        // $member = Member::find(1);
        // echo "Pending to Success -> Member Balance : $member->balance , Estimate Balance :100 \r\n";

        // // Reject Withdrawal
        // echo "In Progress to Pending -> Member Balance : $member->balance , Estimate Balance : 0 \r\n";
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_WITHDRAWAL,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);
        // ProcessTransaction::dispatch($transaction, false);
        // $member = Member::find(1);
        // echo "Pending to Success -> Member Balance : $member->balance , Estimate Balance :100 \r\n";

        // // Success Withdrawal
        // echo "In Progress to Pending -> Member Balance : $member->balance , Estimate Balance : 0 \r\n";
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_WITHDRAWAL,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);
        // ProcessTransaction::dispatch($transaction, true);
        // $member = Member::find(1);
        // echo "Pending to Success -> Member Balance : $member->balance , Estimate Balance :0 \r\n";


        // echo "First Deposit Test \r\n";
        // // Success Deposit
        // echo "In Progress to Pending -> Member Balance : $member->balance , Estimate Balance : 0 \r\n";
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_DEPOSIT,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);
        // ProcessTransaction::dispatch($transaction, true);
        // $member = Member::find(1);
        // echo "Pending to Success -> Member Balance : $member->balance , Estimate Balance :100 \r\n";

        // $member = Member::find(1);
        // echo "First Deposit Count : " . $member->transactions()->where('isFirstDeposit', true)->count() . "\r\n";

        // // Add Sample Deposit for Admin
        // $transaction = Transaction::create([
        //     'unique_id' => Str::uuid(),
        //     'member_id' => $member->id,
        //     'type' => Transaction::TYPE_DEPOSIT,
        //     'amount' => 100,
        //     'status' => Transaction::STATUS_IN_PROGRESS,
        // ]);

        // Add Sample Deposit for Admin
        $transaction = Transaction::create([
            'unique_id' => Str::uuid(),
            'member_id' => $member->id,
            'promotion_id' => 1,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => 100,
            'status' => Transaction::STATUS_IN_PROGRESS,
        ]);

        return 0;
    }
}
