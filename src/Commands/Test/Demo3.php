<?php

namespace App\Console\Commands\Test;

use App\Models\CurrencyProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Demo3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test:Demo3 {date?}';

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
        $productsData = [
            // ['code' => '8K', 'name' => '888King', 'class' => _888king2::class],
            // ['code' => 'JK', 'name' => 'Joker', 'class' => Joker::class],
            // ['code' => 'FH', 'name' => 'Funhouse', 'class' => _Funhouse::class],
            // ['code' => 'DS', 'name' => 'DragonSoft', 'class' => _Dragonsoft::class],
            // ['code' => 'LK', 'name' => 'Lion King', 'class' => _Lionking::class, 'apk_download' => 'https://dl.lk4u.xyz'],
            // ['code' => 'L365', 'name' => 'Lucky365', 'class' => _Lucky365::class],
            // ['code' => 'VP', 'name' => 'Vpower', 'class' => _Vpower::class],
            // ['code' => 'PGS', 'name' => 'PGS', 'class' => _PGS::class],
            // ['code' => 'PP', 'name' => 'Pragmatic Play', 'class' => _PP::class],
            // ['code' => 'L2', 'name' => 'Live22', 'class' => _Live22::class],
            // ['code' => 'AP', 'name' => 'AdvantPlay', 'class' => _AdvantPlay::class],
            // ['code' => 'GW99', 'name' => 'GreatWall99', 'class' => _GW99::class],
            // ['code' => 'XE88', 'name' => 'XE88', 'class' => _XE88::class],
            // ['code' => 'SUNCITY', 'name' => 'SunCity', 'class' => _SunCity::class],
            // ['code' => '3WIN8', 'name' => '3Win8', 'class' => _3Win8::class],
            // ['code' => 'ACE333', 'name' => 'Ace333', 'class' => _ACE333::class],
            // ['code' => 'JILI', 'name' => 'Jili', 'class' => _Jili::class],
            // ['code' => 'SPADE', 'name' => 'Spade Gaming', 'class' => _SpadeGaming::class],
            // ['code' => 'NEXTSPIN', 'name' => 'Next Spin', 'class' => _NextSpin::class],
            // ['code' => 'NETENT', 'name' => 'Netent', 'class' => _Netent::class],
            // ['code' => 'REDTIGER', 'name' => 'Red Tiger', 'class' => _RedTiger::class],
            // ['code' => 'CQ9', 'name' => 'CQ9', 'class' => _CQ9::class],
            // ['code' => 'SPINIX', 'name' => 'SpiniX', 'class' => _SpiniX::class],
            // ['code' => 'FC', 'name' => 'Fa Chai', 'class' => _FaChai::class],
            // ['code' => 'YGR', 'name' => 'Yes Get Rich', 'class' => _YesGetRich::class],
            // ['code' => 'FUNKYGAMES', 'name' => 'Funky Games', 'class' => _FunkyGames::class],
            // ['code' => 'JDB', 'name' => 'JDB Gaming', 'class' => _JDBGaming::class],
            // ['code' => 'RELAXGAMING', 'name' => 'Relax Gaming', 'class' => _RelaxGaming::class],
            // ['code' => 'PLAYSTAR', 'name' => 'Play Star', 'class' => _PlayStar::class],
            // ['code' => 'MEGAPLUS', 'name' => 'Mega Plus', 'class' => _MegaPlus::class],
            // ['code' => 'UUSLOT', 'name' => 'UU Slot', 'class' => _UUSlot::class],
            // ['code' => 'ACEWIN', 'name' => 'Ace Win', 'class' => _AceWin::class],
            // ['code' => 'PLAY8', 'name' => 'Play 8', 'class' => _Play8::class],
            // ['code' => 'HOTROAD', 'name' => 'Hot Road', 'class' => _HotRoad::class],
            // ['code' => 'MONKEYKING', 'name' => 'Monkey King', 'class' => _MonkeyKing::class],
            // ['code' => 'AVATAR', 'name' => 'Avatar', 'class' => _Avatar::class],
            // ['code' => 'SKY1388', 'name' => 'Sky1388', 'class' => _Sky1388::class],
            // ['code' => 'DRAGONGAMING', 'name' => 'Dragon Gaming', 'class' => _DragonGaming::class],
            // ['code' => 'PLAYNGO', 'name' => 'Play N Go', 'class' => _PlayNGo::class],
            // ['code' => 'PTS', 'name' => 'Playtech', 'class' => Playtech::class],
            // ['code' => 'PTL', 'name' => 'Playtech', 'class' => Playtech::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'BG', 'name' => 'Big Gaming', 'class' => BG::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'VG', 'name' => 'Vivo Gaming', 'class' => _VivoGaming::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'EVO', 'name' => 'Evolution', 'class' => _Evolution::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'AB', 'name' => 'AllBet', 'class' => _Allbet::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'DG', 'name' => 'Dream Gaming', 'class' => _Dreaming::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'SEXYBCRT', 'name' => 'Sexy Baccarat', 'class' => _Sexybrct::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'K855', 'name' => 'King855', 'class' => _King855::class, 'category' => Product::CATEGORY_LIVE],

            // ['code' => 'ASTARCASINO', 'name' => 'Astar Casino', 'class' => _AstarCasino::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'SAGAMING', 'name' => 'SA Gaming', 'class' => _SAGaming::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'WMCASINO', 'name' => 'WM Casino', 'class' => _WMCasino::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'WCASINO', 'name' => 'W Casino', 'class' => _WCasino::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'EZUGI', 'name' => 'Ezugi', 'class' => _Ezugi::class, 'category' => Product::CATEGORY_LIVE],
            // ['code' => 'HOGAMING', 'name' => 'HO Gaming', 'class' => _HOGAMING::class, 'category' => Product::CATEGORY_LIVE],

            // ['code' => 'IB', 'name' => 'Saba Sports', 'class' => IBC::class, 'category' => Product::CATEGORY_SPORTS],
            // ['code' => 'OB', 'name' => 'Obet33', 'class' => _Obet33::class, 'category' => Product::CATEGORY_SPORTS],
            // ['code' => 'SB', 'name' => 'SBO', 'class' => _Sportsbook::class, 'category' => Product::CATEGORY_SPORTS],
            // ['code' => 'M8', 'name' => 'M8', 'class' => _M8::class, 'category' => Product::CATEGORY_SPORTS],

            // ['code' => 'SV388', 'name' => 'SV388', 'class' => _SV388::class, 'category' => Product::CATEGORY_SPORTS],

            ['code' => 'RCB988', 'name' => 'RCB988', 'class' => _RCB988::class, 'category' => Product::CATEGORY_SPORTS],

            // ['code' => '9K', 'name' => '918kiss', 'class' => _918kiss::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://yop1.918kiss.com',
            //     'ios_download' => 'https://yop1.918kiss.com',
            //     'apk_download' => 'https://yop1.918kiss.com',],
            // ['code' => 'PS', 'name' => 'Pussy888', 'class' => Pussy888::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://www.pussy888apk.app/',
            //     'ios_download' => 'https://www.pussy888apk.app/',
            //     'apk_download' => 'https://www.pussy888apk.app/',],
            // ['code' => 'MG', 'name' => 'Mega888', 'class' => Mega888::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'http://m.mega566.com',
            //     'ios_download' => 'http://m.mega566.com',
            //     'apk_download' => 'http://m.mega566.com',],
            // ['code' => 'E8', 'name' => 'Evo888', 'class' => _Evo888::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://d.evo366.com/',
            //     'ios_download' => 'https://d.evo366.com/',
            //     'apk_download' => 'https://d.evo366.com/',],
            // ['code' => '918KISS2', 'name' => '918kiss2', 'class' => _918kiss2::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://m.918kiss.ws/',
            //     'ios_download' => 'https://m.918kiss.ws/',
            //     'apk_download' => 'https://m.918kiss.ws/',],
            // ['code' => 'PLAYBOY', 'name' => 'PlayBoy', 'class' => _Playboy::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://mpb8.pb8seaanemone888.com/APP/',
            //     'ios_download' => 'https://mpb8.pb8seaanemone888.com/APP/',
            //     'apk_download' => 'https://mpb8.pb8seaanemone888.com/APP/',],
            // ['code' => '918KAYA', 'name' => '918kaya', 'class' => _918kaya::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://appdownload.jra777.com/',
            //     'ios_download' => 'https://appdownload.jra777.com/',
            //     'apk_download' => 'https://appdownload.jra777.com/',],
            // ['code' => 'SUNCITY', 'name' => 'SunCity', 'class' => _SunCity::class, 'category' => Product::CATEGORY_APP,
            //     'web_download' => 'https://www.suncity2.net/download/',
            //     'ios_download' => 'https://www.suncity2.net/download/',
            //     'apk_download' => 'https://www.suncity2.net/download/',],
        ];

        foreach ($productsData as $data) {
            $product = Product::create([
                'sequence' => 0,
                'code' => $data['code'],
                'name' => $data['name'],
                'category' => $data['category'] ?? Product::CATEGORY_SLOTS,
                'class' => $data['class'],
                'image' => 'http://storewave.work/products/' . $data['code'] . '.webp',
                'status' => Product::STATUS_COMMING_SOON,
                'disclaimer' => ' '
            ]);

            CurrencyProduct::updateOrCreate(
                ['product_id' => $product->id],
                ['currency_id' => 1]
            );
        }

        return 0;
    }
}
