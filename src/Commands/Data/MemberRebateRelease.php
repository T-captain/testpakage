<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\MemberRebate as ModelsMemberRebate;
use Illuminate\Console\Command;

class MemberRebateRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:member_rebate_release {date?}';

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
        $date = $this->argument('date') ?? now()->format('Y-m-d');

        ModelsMemberRebate::where('date', $date)
            ->where('status', ModelsMemberRebate::STATUS_PENDING)
            ->update(['status' => ModelsMemberRebate::STATUS_COMPLETED]);

        Helpers::sendNotification('done rebate release');
        return Command::SUCCESS;
    }
}
