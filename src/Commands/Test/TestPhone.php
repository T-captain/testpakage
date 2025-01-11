<?php

namespace App\Console\Commands\Test;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPhone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:TestPhone';

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
        $response = Http::withoutVerifying()->get('https://sms.360.my/gw/bulk360/v3_0/send.php', [
            'user' => "qM7e53Ab2v",
            'pass' => 'Jv52TNqRKYL4ZnjutKai66xTLM2zk2lgXWKg3ooQ',
            'to' => "60124151522",
            'text' => "Auth Code : 126322",
        ]);
        
        dd($response->getBody());

        return Command::SUCCESS;
    }
}
