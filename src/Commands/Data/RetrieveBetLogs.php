<?php

namespace App\Console\Commands\Data;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetrieveBetLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:retrieve {class?} {date?} {type?} {sleep?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve bet logs hourly or daily based on the type';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $class = $this->argument('class');
        $inputDate = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::now();
        $type = $this->argument('type') ?? 'day';
        $sleep = $this->argument('sleep') ?? 0;

        if (!$class) {
            $this->error('Class name not provided.');
            return 1;
        }

        if ($type === 'hour') {
            for ($hour = 0; $hour < 24; $hour++) {
                $hourlyDate = $inputDate->format('Y-m-d\TH:i:s');
                $this->callClassCommand($class, $hourlyDate);
                sleep($sleep);
            }
        } elseif ($type === 'day') {
            $dailyDate = $inputDate->format('Y-m-d');
            $this->callClassCommand($class, $dailyDate);
        } else {
            $this->error('Invalid type provided. Valid types are "hour" or "day".');
            return 1;
        }

        return 0;
    }

    /**
     * Call the specified class command with the provided date.
     *
     * @param string $class
     * @param string $date
     */
    private function callClassCommand($class, $date)
    {
        // echo json_encode($date);
        $request =  "php artisan {$class} '". $date."'";
        $output = shell_exec($request);
        echo json_encode($output);
        if ($output) {
            $this->info($output);
        } else {
            $this->error("Failed to retrieve bet logs for {$date}");
        }
    }
}
