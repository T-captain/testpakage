<?php

namespace App\Console\Commands\Test;

use App\Helpers\_Live22;
use App\Helpers\_PGS;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetGameLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Test/GetGameList';

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
        $file = explode("\n", file_get_contents(storage_path("games/dragonsoft_game_list.csv")));

        foreach ($file as $game) {
            $game = explode(",", $game);
            $product = null;
            $gameType = '';
            // if (mb_convert_encoding($game[0], "ASCII") === "?FH" || mb_convert_encoding($game[0], "ASCII") === "FH" || $game[0] === "FH" ){
            //     $product = $product_pps;
            //     $gameType = 'FH';
            // }
            // if(mb_convert_encoding($game[0], "ASCII") === "?SLOT" || mb_convert_encoding($game[0], "ASCII") === "SLOT" || $game[0] === "SLOT"){
            //     $product = $product_pps;
            //     $gameType = 'SLOT';
            // }
            // if(mb_convert_encoding($game[0], "ASCII") === "?TABLE" || mb_convert_encoding($game[0], "ASCII") === "TABLE" || $game[0] === "TABLE") {
            //     $product = $product_pps;
            //     $gameType = 'TABLE';
            // }
            
            // if($product != null){
                
            //     Game::updateOrCreate([
            //         'product_id' => $product->id,
            //         'code' => $game[1],
            //         ], [
            //             'name' => [
            //                 'en' => $game[2],
            //                 'cn' => $game[3],
            //             ],
            //             'image' => str_replace(base_path("public"), "", base_path("public/assets/jili/" . $game[1] ."/" ."EN.png")),
            //             'label' => Game::LABEL_NONE,
            //             'meta' => $gameType,
            //         ]); 
            // }

            dd($game);
        }
        // $response = _AdvantPlay::getGameList();
        // $response = _PGS::getGameList();
        // dd($response);
        return Command::SUCCESS;
    }
}
