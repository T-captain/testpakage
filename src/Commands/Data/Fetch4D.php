<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Fetch4D extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:fetch_4d';

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
        $output = array();

        $result = cache()->remember('4d', 60, function () {
            return Http::get('https://mapp.4dking.live/nocache/result_v23.json')->json();
        });

        foreach ($result as $value) {
            if (isset($value['fdData'])) {
                $output[$value['type']] = $value['fdData'];
            }
        }

        $result = cache()->remember('4d_nl', 60, function () {
            return Http::get('https://mapp.4dking.live/nocache/result_nl_v24.json')->json();
        });

        foreach ($result as $value) {
            if (isset($value['fdData'])) {
                $output[$value['type']] = $value['fdData'];
            }
        }

        foreach ($output as $key => $value) {
            $output[$key] = collect($value);
        }

        cache()->put('4d_output', $output);

        return Command::SUCCESS;
    }
}
