<?php

namespace App\Http\Controllers\PG;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Validator};

use App\Http\Controllers\Controller;
use App\Library\PG\HttpExec;
use App\Models\PaymentRequest;
use App\Traits\Processor;

class PgLocalController extends Controller
{
    use Processor;

    protected $config_values;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $this->payment = $payment;
    }

    protected function init(PaymentRequest $payment)
    {
        $config = $this->payment_config($payment->payment_method, 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
    }

    public function paymentQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $this->init($data);

        $customer = json_decode($data->payer_information);
        $merchantOrderId = strtoupper("TR-{$data->id}");
        $amount = round($data->payment_amount);

        $payload = [
            "signature" => "hello",
            "merchantUuid" => $this->config_values->merchant_uuid,
            "partnerReferenceNo" => $merchantOrderId,
            "description" => "Order #{$merchantOrderId}",
            "channelCode" => $this->config_values->method,
            "amount" => $amount,
            "payerName" => $customer->name,
            "payerEmail" => $customer->email,
            "payerPhone" => $customer->phone,
            "callbackUrl" => env('APP_DEBUG') ? env('PG_CALLBACK') : route('pg-local.callback.billing'),
            "returnUrl" => $data->external_redirect_link ? $data->external_redirect_link : route('payment-success'),
            "expiryMinutes" => env('PG_EXPIRY')
        ];

        try {
            $response = HttpExec::hit(
                url: "{$this->config_values->base_url}/api/billing/create",
                request_body: $payload
            );

            $response = json_decode(json_encode($response));
            $request = json_decode(json_encode($payload));

            $responseData = $response->responseData;

            $data->update([
                'currency_code' => 'IDR',
                'request_data' => json_encode($payload),
                'response_data' => json_encode($response),
                'transaction_id' => $responseData->partnerReferenceNo
            ]);

            return Redirect::away($responseData->payUrl);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
    }

    public function callbackBilling(Request $request)
    {
        ## get request data
        $callback_data = file_get_contents('php://input');

        # Jika format callback/response adalah json, decode it
        if (preg_match("/{/", $callback_data)) {
            $callback_data = json_decode($callback_data);
        } elseif (preg_match("/json/", $request->header('content-type'))) {
            # Json
            $callback_data = json_decode($callback_data);
        } elseif (preg_match("/form-urlencoded/", $request->header('content-type'))) {
            # x-www-form-urlencoded
            parse_str($callback_data, $arr_callback);
            $callback_data = (object) $arr_callback;
        } elseif (preg_match("/form/", $request->header('content-type'))) {
            # form biasa
            $callback_data = json_decode(json_encode($_POST));
        }

        # Empty
        if (empty($callback_data)) {
            return Response()->json([
                'error_message' => 'EMPTY',
            ], 422);
        }

        $id = str_replace("TR-", "", $callback_data->partnerReferenceNo);
        $id = strtolower($id);
        $paymentRequest = PaymentRequest::where([
            'id' => $id,
            'is_paid' => 0
        ])->first();
        if (!$paymentRequest) {
            return Response()->json([
                'error_message' => 'NOT_FOUND',
            ], 422);
        }

        $paymentRequest->update([
            'callback_data' => json_encode($callback_data),
        ]);

        if ($callback_data->status == 'PAID') {
            $paymentRequest->update([
                'is_paid' => 1,
            ]);

            if (function_exists($paymentRequest->success_hook)) {
                call_user_func($paymentRequest->success_hook, $paymentRequest);
            }

            return $this->payment_response($paymentRequest, 'success');
        } else {
            if (function_exists($paymentRequest->failure_hook)) {
                call_user_func($paymentRequest->failure_hook, $paymentRequest);
            }
            return $this->payment_response($paymentRequest, 'fail');
        }
    }
}
