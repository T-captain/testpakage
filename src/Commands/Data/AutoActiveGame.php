<?php

namespace App\Console\Commands\Data;

use App\Http\Helpers;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoActiveGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:auto_active_game';

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
        $productList = Product::whereNotIn('code', ['28W', 'VG', 'FH', 'VP'])->get();
        $member = Member::where('id', 6345)->first(); // testing account name : testopen
        if ($member) {
            foreach ($productList as $product) {
                if ($product->status === Product::STATUS_MAINTENANCE) {
                    $member_account = $member->member_accounts()->where('member_id', $member->id)->where('product_id', $product->id)->first();
                    if (!$member_account) {
                        Helpers::sendNotification("Pls register account for maintanance product: {$product->name}");
                        continue;
                    }
                    $productClass = $product->class;
                    $balance = $productClass::balance($member);
                    if ($balance === false) {
                        continue;
                    }
                    $product->status = Product::STATUS_ACTIVE;
                    $product->save();
                    Helpers::sendNotification("Product: {$product->name} is active");
                }
            }
        }

        return Command::SUCCESS;
    }
}
