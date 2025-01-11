<?php

namespace App\Console\Commands;

use App\Models\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOldProviderLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'providerlog:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old provider  log records from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysToKeep = 7;
        $batchSize = 10000;

        Log::where('created_at', '<', now()->subDays($daysToKeep))
            ->limit($batchSize)
            ->delete();
    }
}
