<?php

namespace App\Modules;

use App\Jobs\ProcessTransaction;
use App\Models\Gateway;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Log as ModelsLog;

class AmPay
{
    const USER_DOMAIN = "https://user.amabao.com/";
    const EXT_DOMAIN = "https://ext.amabao.com/";
    const MERCHANT = "";
    const SECRET = "";


    public static function start(Gateway $gateway, $mode = "online_transfer")
    {

        $params = [
            "auth_token" => SELF::MERCHANT,
            "group_id" => "",
            "ref_no" => $gateway->transaction->id,
            "currency" => "MYR",
            "amount" => $gateway->transaction->amount,
            "payment_mode" => SELF::payment_mode($mode),
            "provider" => $gateway->transaction->payment->code,
            "success_link" => env("APP_URL") . "?success=" . __("Deposit has submitted. Please wait for our verification.") . "&token=" . $gateway->transaction->member->token,
            "failure_link" => env("APP_URL") . "?success=" . __("Deposit has submitted. Please wait for our verification.") . "&token=" . $gateway->transaction->member->token,
            "postback_link" => route('transaction.ampay.callback'),
            "first_name" => $gateway->transaction->id,
            "last_name" => $gateway->transaction->id,
            "email" => $gateway->transaction->id . "@gmail.com",
            "phone_no" => $gateway->transaction->id,
            "remark_1" => "",
            "remark_2" => "",
            "remark_3" => "",
            "remark_4" => "",
            "member_id" => "",
        ];

        $params['signature'] = strtoupper(hash(
            'SHA256',
            $params['auth_token'] .
                $params['group_id'] .
                $params['ref_no'] .
                $params['currency'] .
                str_replace(".", "", $params['amount']) .
                $params['payment_mode'] .
                $params['provider'] .
                $params['success_link'] .
                $params['failure_link'] .
                $params['postback_link'] .
                $params['first_name'] .
                $params['last_name'] .
                $params['email'] .
                $params['phone_no'] .
                $params['remark_1'] .
                $params['remark_2'] .
                $params['remark_3'] .
                $params['remark_4'] .
                $params['member_id'] .
                SELF::SECRET
        ));

        $log_id = ModelsLog::addLog([
            'channel' => ModelsLog::CHANNEL_AMPAY_DEPOSIT,
            'function' => 'AMPAY deposit',
            'params' => "Deposit Params " . json_encode($params),
        ]);
        $logForDB = ['id' => $log_id];


        $response = Http::asForm()
            ->withoutVerifying()
            ->withOptions(["verify" => false])
            ->post(SELF::USER_DOMAIN . "payments/initialize", $params)->json();

        $logForDB['trace'] = json_encode($response);
        $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
        ModelsLog::addLog($logForDB);

        $gateway->update([
            'trxno' => $response['data']['ref_no'],
        ]);

        echo "<form id='ampay' method='post' action='" . $response['data']['redirect_link'] . "'>";
        echo "</form>";
        echo "<script type='text/javascript'>";
        echo "document.getElementById('ampay').submit();";
        echo "</script>";
        echo "We are redirect you to the payment page... Please wait..";

        return true;
    }

    public static function callback($request)
    {
        return Cache::lock("ampay-{$request->merc_ref_no}")->get(function () use ($request) {

            $log_id = ModelsLog::addLog([
                'channel' => ModelsLog::CHANNEL_AMPAY_CALLBACK,
                'function' => 'AMPAY Callback',
                'params' => json_encode($request),
            ]);
            $logForDB = ['id' => $log_id];

            try {
                $transaction = Transaction::find($request->merc_ref_no);

                if (!$transaction) {
                    $logForDB['status'] = ModelsLog::STATUS_ERROR;
                    $logForDB['message'] = "Transaction not found!";
                    ModelsLog::addLog($logForDB);
                    throw new Exception("Transaction not found!");
                }

                if ($transaction->status == Transaction::STATUS_SUCCESS) {
                    $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                    $logForDB['message'] = "Transaction already action!";
                    ModelsLog::addLog($logForDB);
                    throw new Exception("Transaction already action!");
                }

                if (SELF::status($request->status) == Gateway::STATUS_IN_PROGRESS) {
                    $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                    $logForDB['message'] = "Transaction still in progress!";
                    ModelsLog::addLog($logForDB);
                    exit();
                }

                $gateway = $transaction->gateway;
                $gateway->amount = $request->amount;
                $gateway->status = SELF::status($request->status);
                $gateway->message = $request->status;
                $gateway->fee = 0;
                $gateway->save();

                if ($gateway->status == Gateway::STATUS_IN_PROGRESS) {
                    $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                    $logForDB['message'] = "Transaction in progress!";
                    ModelsLog::addLog($logForDB);
                    exit();
                }

                ProcessTransaction::dispatch($transaction, $gateway->status === Gateway::STATUS_SUCCESS)->onQueue('transactions');
                $transaction->remark = $request->remark;
                $transaction->action_by = "AmPay";
                $transaction->action_at = date('Y-m-d H:i:s');
                $transaction->save();

                $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                $logForDB['message'] = "Transaction success!";
                ModelsLog::addLog($logForDB);

            } catch (Exception $e) {
                $logForDB['status'] = ModelsLog::STATUS_ERROR;
                $logForDB['message'] = $e->getMessage();
                ModelsLog::addLog($logForDB);
            }
        });

        exit();
    }

    public static function verify(Transaction $transaction)
    {
        // Dont have verify api
        return false;
    }

    public static function status($status)
    {
        if (in_array($status, ["E"])) {
            return Gateway::STATUS_FAIL;
        }

        if (in_array($status, ["S"])) {
            return Gateway::STATUS_SUCCESS;
        }

        return Gateway::STATUS_IN_PROGRESS;
    }

    public static function payment_mode($mode)
    {
        if ($mode == "e_wallet") {
            return "EW";
        }

        return "OB";
    }
}
