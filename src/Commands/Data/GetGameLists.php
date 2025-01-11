<?php

namespace App\Console\Commands\Data;

use App\Helpers\Playtech;
use App\Helpers\_888king2;
use App\Helpers\_918kaya;
use App\Helpers\Joker;
use App\Helpers\_ACE333;
use App\Helpers\_AdvantPlay;
use App\Helpers\_Apollo;
use App\Helpers\_Funhouse;
use App\Helpers\_Dragonsoft;
use App\Helpers\_Jili;
use App\Helpers\_Live22;
use App\Helpers\_PGS;
use App\Helpers\_PP;
use App\Helpers\_PPLive;
use App\Helpers\_Sexybrct;
use App\Helpers\_Vpower;
use App\Helpers\_XE88;
use App\Http\Helpers;
use App\Models\Game;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GetGameLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:get_game_lists';

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
        $functions = [
            'getPlaytechSlots',
            'getJoker',
            'getVpower',
            'getPGS',
            'getDragonsoft',
            'getLive22',
            'getXE88',
            'get918Kaya',
            'getApollo',
            // 'getPPLive',
            // 'get3Win8',
        ];

        foreach ($functions as $function) {
            try {
                $this->{$function}();
                // Helpers::sendNotification($function);
                echo $function . " done\n";
            } catch (\Exception $e) {
                $errorMessage = $function . ': ' . $e->getMessage();
                Helpers::sendNotification($errorMessage);
                echo $errorMessage . "\n";
            }
        }

        return Command::SUCCESS;
    }

    public function getFunhouse()
    {
        $product = Product::where('class', _Funhouse::class)->first();
        if (!$product) {
            return false;
        }
        $response = _Funhouse::getGameList();

        foreach ($response['games'] as $game) {
            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['id'],
            ], [
                'name' => $game['game_name'],
                'image' => 'https://assets.funhouse888.com/gameassets/' . $game['id'] . '/square_200.png',
                'label' => Game::LABEL_NONE,
                'meta' => $game,
            ]);
        }
    }

    public function getPPLive()
    {
        // got game list or lobby
        $product = Product::where('class', _PPLive::class)->first();

        if (!$product) {
            return false;
        }

        $response = _PPLive::getGameList();

        foreach ($response['gameList'] as $game) {
            if ($game['gameTypeID'] == 'vs') {
                continue;
            }
            if ($game['gameTypeID'] == 'lg') {
                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game['gameID'],
                ], [
                    'name' => $game['gameName'],
                    'image' => config('api.PPLIVE_IMAGE_LINK') . '/game_pic/rec/160/' . $game['gameID'] . '.png',
                    'label' => Game::LABEL_NONE,
                    'meta' => $game,
                ]);
            }
        }
    }

    public function get918Kaya()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', _918kaya::class)->whereIn('code', ["918KAYA"])->first();
        if (!$product_pps) {
            return false;
        }
        cache()->forget('gamelist_' . $product_pps->id);
        $file = explode("\n", file_get_contents(app_path("Gamelist/kaya918_gamelist.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;

            if (mb_convert_encoding($game[5], "ASCII") === "Slot") {
                $product = $product_pps;
            } else {
                continue;
            }

            $imageName = str_replace([' ', '.'], '', $game[1]);
            $gamename = "/games/918kaya/" . $game[0] . "_" . $imageName . ".png";

            if ($product != null) {

                Game::firstOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[0],
                ], [
                    'name' => [
                        'en' => $game[1],
                        'cn' => $game[2],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => $game,
                ]);
            }
        }
        return Command::SUCCESS;
    }

    public function getApollo()
    {
        $product = Product::where('class', _Apollo::class)->first();
        if (!$product) {
            return false;
        }
        cache()->forget('gamelist_' . $product->id);
        $gameList = _Apollo::getGameList();

        if (is_array($gameList) && !empty($gameList)) {
            foreach ($gameList as $game) {
                $name = [];
                $name['en'] = $game['name'];

                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game['gType'] ?? '',
                ], [
                    'name' => $name,
                    'image' => $game['image'],
                    'label' => Game::LABEL_NONE,
                    'meta' => $game,
                ]);
            }
        }
    }

    public function getXE88()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', _XE88::class)->whereIn('code', ["XE88"])->first();
        if (!$product_pps) {
            return false;
        }
        cache()->forget('gamelist_' . $product_pps->id);

        $file = explode("\n", file_get_contents(app_path("Gamelist/xe88_gamelist.csv")));

        foreach ($file as $game) {

            $game = explode(",", $game);
            $product = null;
            $gameType = '';

            if (mb_convert_encoding($game[3], "ASCII") === "Slots") {
                $product = $product_pps;
                $gameType = 'SLOT';
            }
            if (mb_convert_encoding($game[3], "ASCII") === "Arcade") {
                $product = $product_pps;
                $gameType = 'ARCADE';
            }
            if (mb_convert_encoding($game[3], "ASCII") === "Table") {
                $product = $product_pps;
                $gameType = 'TABLE';
            }

            $gamename = "/games/XE88/" . $game[0] . ".png";

            if ($product != null && $game[0] != 6) {
                Game::firstOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[0],
                ], [
                    'name' => [
                        'en' => $game[1],
                        'cn' => $game[2],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => $gameType,
                ]);
            }
        }
    }

    public function getJoker()
    {
        $product = Product::where('class', Joker::class)->first();
        if (!$product) {
            return false;
        }

        $response = Joker::getGameList();
        $file = explode("\n", file_get_contents(app_path("Gamelist/JokerTopGameList.csv")));


        foreach ($file as $item) {
            foreach ($response['ListGames'] as $game) {
                if ($game['GameCode'] == $item) {
                    $name = [];
                    foreach ($game['Localizations'] as $locale) {
                        $name[$locale['Language']] = $locale['Name'];
                    }
                    if (strtoupper($game['GameType']) == 'SLOT') {

                        if (in_array($game['GameCode'], $file)) {
                            $label = Game::LABEL_RECOMMENDED;

                            Game::updateOrCreate([
                                'product_id' => $product->id,
                                'code' => $game['GameCode'],
                            ], [
                                'name' => $name,
                                'image' => $game['Image1'],
                                'label' => $label,
                                'meta' => $game,
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($response['ListGames'] as $game) {
            $name = [];
            foreach ($game['Localizations'] as $locale) {
                $name[$locale['Language']] = $locale['Name'];
            }
            if (strtoupper($game['GameType']) == 'SLOT') {
                if (!in_array($game['GameCode'], $file)) {
                    $specialList = [];
                    if ($game['Specials'] != '') {
                        $specialList = explode(",", $game['Specials']);
                    }
                    $label = Game::LABEL_NONE;
                    if (in_array('new', $specialList)) {
                        $label = Game::LABEL_NEW;
                    }
                    if (in_array('hot', $specialList)) {
                        $label = Game::LABEL_HOT;
                    }
                    if (in_array('recommended', $specialList)) {
                        $label = Game::LABEL_RECOMMENDED;
                    }
                    Game::updateOrCreate([
                        'product_id' => $product->id,
                        'code' => $game['GameCode'],
                    ], [
                        'name' => $name,
                        'image' => $game['Image1'],
                        'label' => $label,
                        'meta' => $game,
                    ]);
                }
            }
        }
    }

    public function get888King()
    {
        $product = Product::where('class', _888king2::class)->first();
        if (!$product) {
            return false;
        }
        $response = _888king2::getGameList();

        foreach ($response['list'] as $game) {
            if ($game['game_id'] != 'bslot-005' && $game['game_id'] != 'bslot-007' && $game['game_id'] != 'bslot-016' && $game['game_id'] != 'bslot-030') {
                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game['game_id'],
                ], [
                    'name' => $game['title'],
                    'image' => $game['image_url'],
                    'label' => Game::LABEL_NONE,
                    'meta' => [
                        'url' => $game['url']
                    ],
                ]);
            }
        }
    }

    public function getPlaytechSlots()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', Playtech::class)->whereIn('code', ["PTS"])->first();

        $file = explode("\n", file_get_contents(app_path("Gamelist/playtech_slot_gamelist.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gameImage = null;

            if (mb_convert_encoding($game[0], "ASCII") === "?SLOT" || mb_convert_encoding($game[0], "ASCII") === "SLOT" || $game[0] === "SLOT") {
                $product = $product_pps;
            } else {
                dd(1, mb_convert_encoding($game[4], "ASCII"));
            }
            $gameImage = "/playtech/" . $game[4] . ".jpeg";
            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game[3],
            ], [
                'name' => [
                    'en' => $game[4],
                    'cn' => $game[5],
                ],
                'image' => cdnAsset($gameImage),
                'label' => Game::LABEL_NONE,
                'meta' => [],
            ]);
        }
        return Command::SUCCESS;
    }

    public function getDragonsoft()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', _Dragonsoft::class)->whereIn('code', ["DS"])->first();

        $file = explode("\n", file_get_contents(app_path("Gamelist/dragonsoft_game_list.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gameType = '';
            if (mb_convert_encoding($game[0], "ASCII") === "?捕鱼-Fishing" || mb_convert_encoding($game[0], "ASCII") === "捕鱼-Fishing" || $game[0] === "捕鱼-Fishing") {
                $product = $product_pps;
                $gameType = 'FH';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?老虎机-Slot" || mb_convert_encoding($game[0], "ASCII") === "老虎机-Slot" || $game[0] === "老虎机-Slot") {
                $product = $product_pps;
                $gameType = 'SLOT';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?街机-ARCADE" || mb_convert_encoding($game[0], "ASCII") === "街机-ARCADE" || $game[0] === "街机-ARCADE") {
                $product = $product_pps;
                $gameType = 'ARCADE';
            }

            $gamename = "/games/dragonsoft/" . $game[1] . "_en_web_512.png";

            if ($product != null) {
                $current_game = Game::firstOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[1],
                ], [
                    'name' => [
                        'en' => $game[3],
                        'cn' => $game[2],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => $gameType,
                ]);
                // try {
                //     if (!$this->checkRemoteFile(cdnAsset($gamename))) {
                //         $image = "https://imagefiles.dragoonsoft.com/" . $game[1] . '_' . $game[3] . '_' . $game[2] . "/GAME LOGO/en/" . $game[1] . "_en_web_512.png";
                //         $image = str_replace(' ', '%20', $image);
                //         $image_binary = file_get_contents($image, true);
                //     }
                // } catch (\Throwable $e) {
                //     $current_game->delete();
                // }
            }
        }
    }

    public function getACE333()
    {
        $product = Product::where('class', _ACE333::class)->first();
        if (!$product) {
            return false;
        }

        $response = _ACE333::getGameList();

        foreach ($response['gameList'] as $game) {


            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['gameID'],
            ], [
                'name' => $game['gameName'],
                'image' => dd('https://imagefiles.dragoonsoft.com/' . $game['id'] . '/' . $game['names']['en_us'] . '_' . $game['names']['zh_cn'] . '/GAME LOGO/en/' . $game['id'] . '_en_web_512.png'),
                'label' => Game::LABEL_NONE,
                'meta' => $game,
            ]);
        }
    }

    public function getVpower()
    {
        $product = Product::where('class', _Vpower::class)->first();
        if (!$product) {
            return false;
        }

        $response = _Vpower::getGameList();

        foreach ($response['GameList'] as $game) {

            $name = [];
            $name['cn'] = $game['GameName'];
            $name['en'] = $game['GameName_EN'];

            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['GameID'],
            ], [
                'name' => $name,
                'image' => $game['Image1'],
                'label' => Game::LABEL_NONE,
                'meta' => $game,
            ]);
        }
    }


    public function getPGS()
    {
        $product = Product::where('class', _PGS::class)->first();
        if (!$product) {
            return false;
        }

        $response = _PGS::getGameList();

        foreach ($response as $game) {
            $name = [];
            $name['cn'] = null;
            $name['en'] = null;

            foreach ($game['gameNameMappings'] as $name) {
                if ($name['languageCode'] == 'zh-cn')
                    $name['cn'] = $name['text'];
                if ($name['languageCode'] == 'en-us')
                    $name['en'] = $name['text'];
            }

            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['gameCode'],
            ], [
                'name' => $name,
                'image' => $game['imageUrl'],
                'label' => Game::LABEL_NONE,
                'meta' => $game,
            ]);
        }
    }

    public function getPP()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', _PP::class)->whereIn('code', ["PPL", "PPS"])->first();

        $file = explode("\n", file_get_contents(app_path("Gamelist/pp_game_list.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gamename = null;
            $gameType = '';
            if (mb_convert_encoding($game[0], "ASCII") === "?EGAME" || mb_convert_encoding($game[0], "ASCII") === "EGAME" || $game[0] === "EGAME") {
                $product = $product_pps;
                $gameType = 'EGAME';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?LIVE" || mb_convert_encoding($game[0], "ASCII") === "LIVE" || $game[0] === "LIVE") {
                $product = $product_pps;
                $gameType = 'LIVE';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?SLOT" || mb_convert_encoding($game[0], "ASCII") === "SLOT" || $game[0] === "SLOT") {
                $product = $product_pps;
                $gameType = 'SLOT';
            }
            $gamename = "/pp/" . $game[1] . "/" . "en.png";

            if ($product != null) {
                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[1],
                ], [
                    'name' => [
                        'en' => $game[2],
                        'cn' => $game[3],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => $gameType,
                ]);
            }
        }
        return Command::SUCCESS;
    }

    public function getSexybcrt()
    {
        $product_ppl = Product::where('category', Product::CATEGORY_LIVE)->where('class', _Sexybrct::class)->whereIn('code', ["SXYB"])->first();

        $file = explode("\n", file_get_contents(app_path("Gamelist/sexybcrt_game_list.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gamename = null;

            if (mb_convert_encoding($game[0], "ASCII") === "?LIVE" || mb_convert_encoding($game[0], "ASCII") === "LIVE" || $game[0] === "LIVE" || mb_convert_encoding($game[0], "ASCII") === "?LIVE" || mb_convert_encoding($game[0], "ASCII") === "LIVE" || $game[0] === "LIVE") {
                $product = $product_ppl;
            } else {
                dd(1, mb_convert_encoding($game[0], "ASCII"));
            }
            $gamename = "/sexybcrt/" . $game[1] . "/" . "EN.png";
            if ($product != null) {
                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[1],
                ], [
                    'name' => [
                        'en' => $game[2],
                        'cn' => $game[3],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => [],
                ]);
            }
        }
    }

    public function getJili()
    {
        $product_pps = Product::where('category', Product::CATEGORY_SLOTS)->where('class', _Jili::class)->whereIn('code', ["JL"])->first();

        $file = explode("\n", file_get_contents(app_path("Gamelist/jili_game_list.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gamename = null;
            $gameType = '';
            if (mb_convert_encoding($game[0], "ASCII") === "?FH" || mb_convert_encoding($game[0], "ASCII") === "FH" || $game[0] === "FH") {
                $product = $product_pps;
                $gameType = 'FH';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?SLOT" || mb_convert_encoding($game[0], "ASCII") === "SLOT" || $game[0] === "SLOT") {
                $product = $product_pps;
                $gameType = 'SLOT';
            }
            if (mb_convert_encoding($game[0], "ASCII") === "?TABLE" || mb_convert_encoding($game[0], "ASCII") === "TABLE" || $game[0] === "TABLE") {
                $product = $product_pps;
                $gameType = 'TABLE';
            }
            $gamename = "/jili/" . $game[1] . "/" . "EN.png";
            if ($product != null) {

                Game::updateOrCreate([
                    'product_id' => $product->id,
                    'code' => $game[1],
                ], [
                    'name' => [
                        'en' => $game[2],
                        'cn' => $game[3],
                    ],
                    'image' => cdnAsset($gamename),
                    'label' => Game::LABEL_NONE,
                    'meta' => $gameType,
                ]);
            }
        }
    }

    public function getLive22()
    {
        $product = Product::where('class', _Live22::class)->first();
        if (!$product) {
            return false;
        }

        $response = _Live22::getGameList();

        foreach ($response as $game) {
            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['GameCode'],
            ], [
                'name' => $game['GameName'],
                'image' => $game['ImageUrl'],
                'label' => Game::LABEL_NONE,
                'meta' => $game['GameType'],
            ]);
        }
    }

    public function getAdvantPlay()
    {
        $product = Product::where('class', _AdvantPlay::class)->first();
        if (!$product) {
            return false;
        }

        $response = _AdvantPlay::getGameList();

        foreach ($response['Games'] as $game) {
            $name = [];
            $name['cn'] = $game['IconList'][1]['Name'];
            $name['en'] = $game['IconList'][1]['Name'];

            Game::updateOrCreate([
                'product_id' => $product->id,
                'code' => $game['GameCode'],
            ], [
                'name' => $name,
                'image' => $game['IconList'][1]['Url']['492x660']['bkg'],
                'label' => Game::LABEL_NONE,
                'meta' => $game,
            ]);
        }
    }

    function checkRemoteFile($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace(" ", "%20", $url));
        // don't download content
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result !== FALSE) {
            return $code == 200;
        }

        return false;
    }
}
