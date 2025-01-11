<?php

namespace App\Console\Commands\ExternalAPI;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Helpers;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ExternalAPI:Test';

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

        $currentDateTime = new DateTime(); // creates a DateTime object with the current date and time
        $formattedDateTime = $currentDateTime->format('Y-m-d H:00:00'); // formats date with hours, setting minutes and seconds to 00

        Helpers::sendNotification($formattedDateTime);


        return Command::SUCCESS;
    }
}
