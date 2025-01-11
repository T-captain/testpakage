<?php

namespace App\Modules;

use App\Http\Helpers;
use App\Jobs\ProcessTransaction;
use App\Models\Gateway;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Log as ModelsLog;

class PowerPG
{
    const KIOSK = "https://merchantdgpaybo.pwpgbo.com/index.html";
    const KIOSK_USERNAME = "dgpay_1895347";
    const KIOSK_PASSWORD = "Aa12888";
    const MERCHANT_CODE = "dgpay_189";
    const MERCHANT_KEY = "71fd69e5-4c6b-48a4-878d-4fa68104da3b";
    const API_URL = "https://dgpayapi.pwpgbo.com";
    const SERVER_IP = "18.136.229.15";

    public static function start(Gateway $gateway, $mode = "online_transfer")
    {
        $orderId = $gateway->transaction->id;
        $providerId = 5;
        $providerType = ($mode == "e_wallet") ? 20 : 10;
        $currency = $gateway->currency;
        $amount = $gateway->transaction->amount;
        $callbackUrl = route('transaction.powerpg.callback');
        $data = json_encode([
            'fromBankCode' => $gateway->transaction->payment->identify,
            "redirectUrl" => env("APP_URL") . "?success=" . __("Deposit has submitted. Please wait for our verification."),
        ]);
        $opCode = SELF::MERCHANT_CODE;
        $reqDateTime = $gateway->transaction->created_at->format('Y-m-d h:i:s');
        $securityToken = md5($orderId . $providerId . $providerType . $currency . $amount . $reqDateTime . $opCode . SELF::MERCHANT_KEY);

        $request = Http::asForm()->post(SELF::API_URL . "/ajax/api/deposit", $params = [
            'orderId' => $orderId,
            'providerId' => $providerId,
            'providerType' => $providerType,
            'currency' => $currency,
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
            'data' => $data,
            'opCode' => $opCode,
            'reqDateTime' => $reqDateTime,
            'securityToken' => $securityToken,
        ]);

        $log_id = ModelsLog::addLog([
            'channel' => ModelsLog::CHANNEL_POWERPG_DEPOSIT,
            'function' => 'PowerPG',
            'params' => "Deposit Params " . json_encode($params),
        ]);
        $logForDB = ['id' => $log_id];

        if ($request->failed()) {
            $logForDB['status'] = ModelsLog::STATUS_ERROR;
            $logForDB['trace'] = json_encode($request->body());
            ModelsLog::addLog($logForDB);
            Helpers::sendNotification("DGpay deposit failed");
            return null;
        }

        $response = $request->json();
        $logForDB['trace'] = json_encode($response);

        if ($response['code'] == "-1") {
            $logForDB['status'] = ModelsLog::STATUS_ERROR;
            ModelsLog::addLog($logForDB);
            $responseMessage = $response['message'] ?? '';
            Helpers::sendNotification_dgpay("Hi team\n\nError Code -1 encountered during a transaction.\n\nResponse message: " . $responseMessage . "\n\nYour prompt assistance would be greatly appreciated, thanks.");
            return redirect()->route('deposit', ['successdeposit' => '1'])->with('error', __("Banking services are currently unavailable. Please try again later."));
        }

        if ($response['code'] == "-140") {
            $logForDB['status'] = ModelsLog::STATUS_ERROR;
            ModelsLog::addLog($logForDB);
            return redirect()->route('deposit', ['successdeposit' => '1'])->with('error', __("Duplicate Order ID. Please try again later."));
        }

        if ($response['code'] == "-175") {
            $logForDB['status'] = ModelsLog::STATUS_ERROR;
            ModelsLog::addLog($logForDB);
            Helpers::sendNotification_dgpay("Hi team\n\nError 175 encountered during a $mode transaction.\n\nYour prompt assistance would be greatly appreciated, thanks.");
            return redirect()->route('deposit', ['successdeposit' => '1'])->with('error', __("Banking services are currently unavailable. Please try again later."));
        }

        if ($response['code'] != "0") {
            $logForDB['status'] = ModelsLog::STATUS_ERROR;
            ModelsLog::addLog($logForDB);
            Helpers::sendNotification("DGpay deposit failed, Error code: " . $response['code']);
            return redirect()->route('deposit', ['successdeposit' => '1'])->with('error', __("Banking services are currently unavailable. Please try again later."));
        }

        if (isset($response['refId'])) {

            $gateway->update([
                'trxno' => $response['refId'],
            ]);
            $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
            ModelsLog::addLog($logForDB);

            return view('payments.redirect', [
                'url' => $response['paymentUrl'],
            ]);
        }

        return null;
    }

    public static function callback($request)
    {
        return Cache::lock("powerpg-{$request->orderId}")->get(function () use ($request) {
            try {

                $log_id = ModelsLog::addLog([
                    'channel' => ModelsLog::CHANNEL_POWERPG_CALLBACK,
                    'function' => 'PowerPG Callback',
                    'params' => json_encode($request),
                ]);
                $logForDB = ['id' => $log_id];


                $transaction = Transaction::find($request->orderId);

                if (!$transaction) {
                    $logForDB['status'] = ModelsLog::STATUS_ERROR;
                    $logForDB['message'] = "Transaction not found!";
                    ModelsLog::addLog($logForDB);
                    throw new Exception("Transaction not found!");
                }

                if (SELF::status($request->status) == Gateway::STATUS_IN_PROGRESS) {
                    $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                    $logForDB['message'] = "Transaction in progress!";
                    ModelsLog::addLog($logForDB);
                    return response()->json([
                        'code' => "0",
                        'description' => "Success",
                    ]);
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

                if ($gateway->status === Gateway::STATUS_SUCCESS && $transaction->status === Transaction::STATUS_FAIL) {

                    $message = 'STARGAME X DGpay second callback: Gateway - ' . json_encode($gateway) . ', Transaction - ' . json_encode($transaction);
                    Helpers::sendNotification($message);
                    $transaction->status = Transaction::STATUS_PENDING;
                    $transaction->save();
                }

                ProcessTransaction::dispatch($transaction, $gateway->status === Gateway::STATUS_SUCCESS)->onQueue('transactions');
                $transaction->remark = $request->remark;
                $transaction->action_by = "PowerPG";
                $transaction->action_at = date('Y-m-d H:i:s');
                $transaction->save();
            } catch (Exception $e) {
                $logForDB['status'] = ModelsLog::STATUS_ERROR;
                $logForDB['message'] = "Transaction " . $e->getMessage();
                ModelsLog::addLog($logForDB);
                return response()->json([
                    'code' => "-100",
                    'description' => "$e",
                ]);
            }

            $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
            $logForDB['message'] = "Transaction OK!";
            ModelsLog::addLog($logForDB);

            return response()->json([
                'code' => "0",
                'description' => "Success",
            ]);
        });
    }

    public static function verify(Transaction $transaction)
    {
        // Dont have verify api
        return false;
    }

    public static function status($status)
    {
        switch ($status) {
            case "-20":
                return Gateway::STATUS_FAIL;
            case "-10":
                return Gateway::STATUS_FAIL;
            case "0":
                return Gateway::STATUS_IN_PROGRESS;
            case "10":
                return Gateway::STATUS_IN_PROGRESS;
            case "20":
                return Gateway::STATUS_SUCCESS;
            default:
                return Gateway::STATUS_IN_PROGRESS;
        }
    }
}
