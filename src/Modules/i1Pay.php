<?php

namespace App\Modules;

use App\Http\Helpers;
use App\Jobs\ProcessTransaction;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Log as ModelsLog;
use Exception;

class i1Pay
{
    const STATUS_ON = 1,
        STATUS_VOIDED = 2,
        STATUS_FAILED = 3,
        STATUS_REJECTED = 4,
        STATUS_PROCESSING = 5,
        STATUS_PENDING = 6,
        STATUS_FORFEIT = 7,
        STATUS_REFUNDED = 8;

    public static function start(Gateway $gateway)
    {
        $log_channel = "i1pay";
        Log::channel($log_channel)->debug("Starting deposit.... ! ");

        $token = config('api.PAYMENT_I1PAY_TOKEN');
        $api_user_code = $gateway->member_id;
        $callback_url = route('transaction.i1pay.callback');
        $reference_id = (string)$gateway->transaction->id;
        Log::debug("reference_id: " . gettype($reference_id));
        $amount = $gateway->transaction->amount;
        $bank = $gateway->transaction->payment->code;

        $params = [
            'token' => $token,
            'ApiUserCode' => $api_user_code,
            'CallBackUrl' => $callback_url,
            'ReferenceId' => $reference_id,
            'Amount' => $amount,
            'BankCode' => $bank,
        ];

        Log::channel($log_channel)->debug("Deposit Params " . json_encode($params));

        $log_id = ModelsLog::addLog([
            'channel' => ModelsLog::CHANNEL_I1PAY_DEPOSIT,
            'function' => 'i1Pay Deposit',
            'params' => "Deposit Params " . json_encode($params),
        ]);
        $logForDB = ['id' => $log_id];

        $form = [
            'url' => config('api.PAYMENT_I1PAY_URL') . "/Launch.aspx",
            'params' => $params
        ];

        Log::channel($log_channel)->debug("End deposit.... ! ");

        return view('payments.redirect', [
            'form' => $form,
            'url' => null,
            'method' => 'POST',
        ]);
    }

    public static function callback($request)
    {
        // Helpers::sendNotification("i1pay-{$request->referenceId} callback:  " . json_encode($request->all()));

        return Cache::lock("i1pay-{$request->referenceId}")->get(function () use ($request) {

            $logForDB = [
                'channel' => ModelsLog::CHANNEL_I1PAY_CALLBACK,
                'function' => 'i1Pay Callback',
                'params' => json_encode($request->all()),
            ];

            try {
                Log::channel('i1pay')->debug("Callback.... !");
                Log::channel('i1pay')->debug(json_encode($request->all()));
                $transaction = Transaction::where('id', (int)$request->referenceId)->first();

                if (!$transaction) {
                    $logForDB['status'] = ModelsLog::STATUS_ERROR;
                    $logForDB['message'] = "Transaction not found!";
                    ModelsLog::addLog($logForDB);
                    throw new Exception("Transaction not found!");
                }

                if ($transaction->status != Transaction::STATUS_PENDING && $transaction->status != Transaction::STATUS_FAIL) {
                    Log::channel('i1pay')->debug("Transaction already action !");
                    $logForDB['status'] = ModelsLog::STATUS_ERROR;
                    $logForDB['message'] = "Transaction already action!";
                    ModelsLog::addLog($logForDB);
                    throw new Exception("Transaction already action!");
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
                $gateway->message = $request->itemDesc;
                $gateway->fee = 0;
                $gateway->save();

                if ($gateway->status == Gateway::STATUS_IN_PROGRESS) {
                    Log::channel('i1pay')->debug("Transaction in progress.... ! ");
                    $logForDB['status'] = ModelsLog::STATUS_SUCCESS;
                    $logForDB['message'] = "Transaction in progress!";
                    ModelsLog::addLog($logForDB);
                    exit();
                }

                //here is for second time call back
                if ($gateway->status === Gateway::STATUS_SUCCESS && $transaction->status === Transaction::STATUS_FAIL) {
                    $message = config('api.APP_NAME') . ' X i1pay second callback: Gateway - ' . json_encode($gateway) . ', Transaction - ' . json_encode($transaction);
                    Helpers::sendNotification($message);
                    $transaction->status = Transaction::STATUS_PENDING;
                    $transaction->save();
                }

                Log::channel('i1pay')->debug("Transaction proceed.... ! ");
                Log::channel('i1pay')->debug("Gateway details: " . json_encode($gateway));
                Log::channel('i1pay')->debug("Transaction " . (($gateway->status == Gateway::STATUS_SUCCESS) ? "YES" : "NO"));
                ProcessTransaction::dispatch($transaction, $gateway->status === Gateway::STATUS_SUCCESS)->onQueue('transactions');
                $transaction->remark = $request->itemDesc;
                $transaction->action_by = "i1pay";
                $transaction->action_at = date('Y-m-d H:i:s');
                $transaction->save();

                Log::channel('i1pay')->debug("Transaction OK.... ! ");
            } catch (Exception $e) {
                Log::channel('i1pay')->debug("Transaction ERROR ! ");
                Log::channel('i1pay')->debug("Transaction $e ");
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

    public static function status($status)
    {
        switch ($status) {
            case self::STATUS_ON:
                return Gateway::STATUS_SUCCESS;
            case self::STATUS_PROCESSING:
            case self::STATUS_PENDING:
                return Gateway::STATUS_IN_PROGRESS;
            default:
                return Gateway::STATUS_FAIL;
        }
    }
}
