<?php

namespace App\Console\Commands\Test;

use App\Helpers\_918kiss;
use App\Helpers\_AWC;
use App\Helpers\_CommonCache;
use App\Helpers\_Vpower;
use App\Http\Helpers;
use App\Jobs\ProcessTransaction;
use App\Models\Product;
use Illuminate\Console\Command;
use App\Models\BetLog;
use App\Models\Member;
use App\Models\MemberBonus;
use App\Models\ProductReport;
use App\Models\ReportMemberBonuses;
use App\Models\Setting;
use App\Models\Transaction;
use App\Modules\_AWCController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Storage;

class Demo extends Command
{

    public $reports = [];
    public $reports2 = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:Demo {date?}';

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
        echo bcrypt("gcboss1234");



        return 0;
    }
}
