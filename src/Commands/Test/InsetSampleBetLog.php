<?php

namespace App\Console\Commands\Test;

use App\Models\BetLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InsetSampleBetLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

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

        $sample_data = json_decode('[{
            "ID": "115481295450",
            "Product": "IBC",
            "Category": "SPORT",
            "Username": "SPEY2ZAUE",
            "Stake": 500.0000,
            "ValidStake": 0.0000,
            "Payout": 0.0000,
            "JackpotWin": 0.0000,
            "ProgressiveShare": 0.000000,
            "Commission": 0.0000,
            "CashOut": 0.0000,
            "PayoutStatus": "OPEN",
            "BetStatus": "OPEN",
            "AccountDate": "2022-05-28 00:00:00",
            "RoundDateTime": "2022-05-28 19:41:14",
            "ModifiedDateTime": "2022-05-28 19:41:53",
            "BetDetail": {
              "ID": "115481295450",
              "RefNo": "",
              "MatchID": "53213024",
              "LeagueID": "1837",
              "HomeID": "8",
              "AwayID": "22785",
              "LeagueName": [
                {
                  "ID": "1837",
                  "Name": "*UEFA CHAMPIONS LEAGUE",
                  "Language": "en-US"
                },
                {
                  "ID": "1837",
                  "Name": "*欧洲冠军杯",
                  "Language": "zh-CN"
                },
                {
                  "ID": "1837",
                  "Name": "ยูฟ่า แชมเปี้ยนส์ลีก",
                  "Language": "th-TH"
                },
                {
                  "ID": "1837",
                  "Name": "UEFA 챔피온스 리그",
                  "Language": "ko-KR"
                }
              ],
              "HomeName": [
                {
                  "ID": "8",
                  "Name": "Liverpool",
                  "Language": "en-US"
                },
                {
                  "ID": "8",
                  "Name": "利物浦",
                  "Language": "zh-CN"
                },
                {
                  "ID": "8",
                  "Name": "ลิเวอร์พูล",
                  "Language": "th-TH"
                },
                {
                  "ID": "8",
                  "Name": "리버풀",
                  "Language": "ko-KR"
                }
              ],
              "AwayName": [
                {
                  "ID": "22785",
                  "Name": "Real Madrid ",
                  "Language": "en-US"
                },
                {
                  "ID": "22785",
                  "Name": "皇家马德里",
                  "Language": "zh-CN"
                },
                {
                  "ID": "22785",
                  "Name": "เรอัล มาดริด",
                  "Language": "th-TH"
                },
                {
                  "ID": "22785",
                  "Name": "레알 마드리드",
                  "Language": "ko-KR"
                }
              ],
              "SportsType": "Soccer",
              "MatchDateTime": "2022-05-28 15:00:00",
              "BetTeam": "Home",
              "BetTypeID": "1",
              "BetType": "Handicap",
              "Handicap": -0.5,
              "Odds": 0.96,
              "OriginalStake": 0.0,
              "BuyBackAmount": 0.0,
              "Info": null,
              "OddsType": "MalayOdds",
              "HomeHandicap": 0.5,
              "AwayHandicap": 0.0,
              "WinLoss": "RUNNING",
              "HomeScore": "0",
              "AwayScore": "0",
              "Platform": "WebSite",
              "IsLive": true,
              "IsParlay": false,
              "IsCashOutData": false,
              "ParlayData": null,
              "VersionKey": "55925967",
              "IsFirstHalf": false,
              "CashOutData": null
            }
          }]', true);
        $betDetail = [
            'bet_id' => $sample_data[0]['ID'],
            'product' => $sample_data[0]['Product'],
            'category' => $sample_data[0]['Category'],
            'username' => $sample_data[0]['Username'],
            'stake' => $sample_data[0]['Stake'],
            'valid_stake' => $sample_data[0]['ValidStake'],
            'payout' => $sample_data[0]['Payout'],
            'winlose' => $sample_data[0]['Stake'] - $sample_data[0]['Payout'],
            'jackpot_win' => $sample_data[0]['JackpotWin'],
            'progressive_share' => $sample_data[0]['ProgressiveShare'],
            'payout_status' => $sample_data[0]['PayoutStatus'],
            'bet_status' => $sample_data[0]['BetStatus'],
            'account_date' => $sample_data[0]['AccountDate'],
            'round_at' => Carbon::parse($sample_data[0]['RoundDateTime'], 'Africa/Freetown')->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
            'modified_datetime' => Carbon::parse($sample_data[0]['ModifiedDateTime'], 'Africa/Freetown')->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
            'bet_detail' => json_encode($sample_data[0]['BetDetail']),
        ];

        $upserts = [$betDetail];
        BetLog::upsert($upserts, ['bet_id'], [
            'product',
            'category',
            'username',
            'stake',
            'valid_stake',
            'payout',
            'winlose',
            'jackpot_win',
            'progressive_share',
            'payout_status',
            'bet_status',
            'account_date',
            'round_at',
            'modified_datetime',
            'bet_detail'
        ]);

        dd($sample_data);
        return 0;
    }
}
