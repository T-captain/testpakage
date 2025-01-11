<?php

namespace App\Helpers;

use App\Models\Member;
use App\Models\Product;
use App\Models\Transfer;
use App\Modules\_MKController;

class _MK
{
    public static function create(Member $member)
    {
        $password = SELF::randomPassword();

        $response = _MKController::init("UserInfo/CreatePlayer", [
            'ID' => SELF::randomGUID(),
            'Method' => 'CreatePlayer',
            'PlayerName' => str_replace(' ', '', $member->full_name),
            'SN' => config('api.MK_SN'),
            'PlayerCode' => $member->code,
        ]);

        if (!$response['status']) {
            return false;
        }

        return $member->member_accounts()->updateOrCreate([
            'member_id' => $member->id,
            'product_id' => $member->product_id,
        ], [
            'account' => $member->code,
            'username' => $member->code,
            'password' => $password,
        ]);
    }

    public static function check(Member $member)
    {
        $member_account = $member->member_accounts()->where('member_id', $member->id)->where('product_id', $member->product_id)->first();
        if (!$member_account) {
            return SELF::create($member);
        }

        return $member_account;
    }

    public static function resetPassword($product, $user)
    {
        return true;
    }

    public static function account(Member $member)
    {
        if ($account = SELF::check($member)) {
            return $account;
        }
        return SELF::create($member);
    }

    public static function balance($member)
    {
        $member_account = SELF::check($member);
        if (!$member_account) {
            return false;
        }

        return SELF::account_balance($member_account);
    }

    public static function account_balance($member_account)
    {
        $response = _MKController::init("Account/GetBalance", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'GetBalance',
            'LoginId' => $member_account->username,
        ]);

        if (!$response['status']) {
            return false;
        }

        return $response['data']['result'];
    }

    public static function deposit($member, $transfer)
    {
        $member_account = SELF::check($member);
        if (!$member_account) {
            return false;
        }

        return SELF::account_deposit($member_account, $transfer);
    }

    public static function account_deposit($member_account, $transfer)
    {
        $response = _MKController::init("Account/SetBalanceTransfer", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'SetBalanceTransfer',
            'LoginId' => $member_account->username,
            'Amount' => $transfer->amount,
        ]);

        if (!$response['status']) {
            return false;
        }
        return $response['status'];
    }

    public static function withdrawal($member, $transfer)
    {
        $member_account = SELF::check($member);
        if (!$member_account) {
            return false;
        }

        return SELF::account_withdrawal($member_account, $transfer);
    }

    public static function account_withdrawal($member_account, $transfer)
    {
        $response = _MKController::init("Account/SetBalanceTransfer", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'SetBalanceTransfer',
            'LoginId' => $member_account->username,
            'Amount' => $transfer->amount * -1,
        ]);

        if (!$response['status']) {
            return false;
        }
        
        return $response['status'];
    }

    public static function checkTransaction($uuid)
    {
        $transfer =  Transfer::where('uuid', $uuid)->first();
        if (!$transfer) {
            return false;
        }

        $member_account = SELF::check($transfer->member);
        if (!$member_account) {
            return false;
        }


        $response = _MKController::init("Account/GetTransferById", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'GetTransferById',
            'LoginId' => $member_account->username,
            'RefId' => $transfer->uuid,
        ]);

        if (!$response['status']) {
            return [
                'status' => Transfer::STATUS_FAIL,
                'remark' => json_encode($response),
            ];
        }

        return [
            'status' => Transfer::STATUS_SUCCESS,
            'remark' => json_encode($response),
        ];
    }

    public static function launch(Member $member, $gameid = null, $isMobile = false, $blimit = null)
    {
        $member_account = SELF::check($member);

        if (!$member_account) {
            return Product::ERROR_ACCOUNT;
        }

        $response = _MKController::init("UserInfo/GetLoginH5", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'GetLoginH5',
            'LoginId' => $member_account->username,
        ]);

        if (!$response['status']) {
            return Product::ERROR_PROVIDER_MAINTENANCE;
        }

        $data = array(
            'CallBackUrl' => '',
            'Language' => _MKController::getLocale()
        );

        $jsonObject = json_encode($data);
        $parametersBytes = utf8_encode($jsonObject);
        $parametersValue = base64_encode($parametersBytes);

        return [
            'url' => config('api.MK_GAME_LINK') . $response['data']['loginUrl'] . '&' . $parametersValue
        ];
    }

    public static function bet_limit($game, $data)
    {
        return true;
    }

    public static function getBets($startDate, $endDate, $page = 1)
    {
        $response = _MKController::init("Game/GetGameRecordByTime", [
            'SN' => config('api.MK_SN'),
            'ID' => SELF::randomGUID(),
            'Method' => 'GetGameRecordByTime',
            'StartTime' => $startDate,
            'EndTime' => $endDate,
            'PageSize' => 2000,
            'PageIndex' => $page,
        ]);

        if (!$response['status']) {
            return false;
        }

        if ($response['data']['totalPage'] > 1 && $page < $response['data']['totalPage']) {
            return array_merge($response['data']['item'], SELF::getBets($startDate, $endDate, $page + 1));
        }

        return $response['data']['item'];
    }

    public static function getTimestamp()
    {
        return time();
    }

    public static function randomPassword($len = 8)
    {
        if ($len < 8) {
            $len = 8;
        }

        $sets = array();
        $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '123456789';

        $password = '';

        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }

        //use all characters to fill up to $len
        while (strlen($password) < $len) {
            //get a random set
            $randomSet = $sets[array_rand($sets)];

            //add a random char from the random set
            $password .= $randomSet[array_rand(str_split($randomSet))];
        }

        //shuffle the password string before returning!
        return str_shuffle($password);
    }

    public static function randomGUID($len = 8)
    {
        if ($len < 8) {
            $len = 8;
        }

        $sets = array();
        $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '123456789';

        $password = '';

        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }

        //use all characters to fill up to $len
        while (strlen($password) < $len) {
            //get a random set
            $randomSet = $sets[array_rand($sets)];

            //add a random char from the random set
            $password .= $randomSet[array_rand(str_split($randomSet))];
        }

        //shuffle the password string before returning!
        return str_shuffle($password);
    }
}