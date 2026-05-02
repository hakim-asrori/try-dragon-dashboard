<?php

namespace App\Http\Controllers\PG;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Redirect, Validator};

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use App\Library\PG\HttpExec;

class DuitkuController extends Controller
{
    use Processor;

    protected $config_values;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $this->payment = $payment;
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
            "merchantCode" => $this->config_values->merchant_code,
            "paymentAmount" => $amount,
            "paymentMethod" => $this->config_values->method,
            "merchantOrderId" => strtoupper($merchantOrderId),
            "productDetails" => "Order #{$merchantOrderId}",
            "customerVaName" => $customer->name,
            "email" => $customer->email,
            "phoneNumber" => $customer->phone,
            "callbackUrl" => env('APP_DEBUG') ? env('PG_CALLBACK') : route('duitku.callback.billing'),
            "returnUrl" => route('payment-success'),
            "signature" => $this->generateSignature(
                merchantCode: $this->config_values->merchant_code,
                referenceId: $merchantOrderId,
                amount: $amount,
                apiKey: $this->config_values->api_key,
                type: "BILLING"
            ),
            "expiryPeriod" => env('PG_EXPIRY')
        ];

        try {
            $response = HttpExec::hit(
                url: "{$this->config_values->base_url}/webapi/api/merchant/v2/inquiry",
                request_body: $payload
            );

            $response = json_decode(json_encode($response));
            $request = json_decode(json_encode($payload));

            $data->update([
                'currency_code' => 'IDR',
                'request_data' => json_encode($payload),
                'response_data' => json_encode($response),
                'transaction_id' => $response->reference
            ]);

            return Redirect::away($response->paymentUrl);
        } catch (\Throwable $th) {
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

        $id = str_replace("TR-", "", $callback_data->merchantOrderId);
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

        if ($callback_data->resultCode == '00') {
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

    protected function init(PaymentRequest $payment)
    {
        $config = $this->payment_config($payment->payment_method, 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
    }

    protected function generateSignature(
        ?string $referenceId = "",
        ?string $amount = "",
        ?string $email = "",
        ?string $time = "",
        ?string $bankCode = "",
        ?string $bankAccount = "",
        ?string $bankAccountName = "",
        ?string $customerRefNumber = "",
        ?string $disburseId = "",
        ?string $purpose = "",
        ?string $clearingType = "",
        ?string $apiKey = "",
        ?string $secretKey = "",
        ?string $merchantCode = "",
        string $type = "BILLING"
    ): string {

        $patterns = [
            "BILLING" => [
                "algo" => "md5",
                "fields" => ["merchantCode", "referenceId", "amount", "apiKey"]
            ],

            "BILLING_INQUIRY" => [
                "algo" => "md5",
                "fields" => ["merchantCode", "referenceId", "apiKey"]
            ],

            "PAYMENT_METHOD" => [
                "algo" => "sha256",
                "fields" => ["merchantCode", "amount", "time", "apiKey"],
                "key" => "apiKey"
            ],

            "LIST_BANK" => [
                "algo" => "sha256",
                "fields" => ["email", "time", "secretKey"]
            ],

            "BALANCE" => [
                "algo" => "sha256",
                "fields" => ["email", "time", "secretKey"]
            ],

            "ACCOUNT_INQUIRY" => [
                "algo" => "sha256",
                "fields" => ["email", "time", "bankCode", "bankAccount", "amount", "purpose", "secretKey"]
            ],

            "ACCOUNT_INQUIRY_CLEARING" => [
                "algo" => "sha256",
                "fields" => ["email", "time", "bankCode", "clearingType", "bankAccount", "amount", "purpose", "secretKey"]
            ],

            "DISBURSE" => [
                "algo" => "sha256",
                "fields" => [
                    "email",
                    "time",
                    "bankCode",
                    "bankAccount",
                    "bankAccountName",
                    "customerRefNumber",
                    "amount",
                    "purpose",
                    "disburseId",
                    "secretKey"
                ]
            ],

            "DISBURSE_CLEARING" => [
                "algo" => "sha256",
                "fields" => [
                    "email",
                    "time",
                    "bankCode",
                    "clearingType",
                    "bankAccount",
                    "bankAccountName",
                    "customerRefNumber",
                    "amount",
                    "purpose",
                    "disburseId",
                    "secretKey"
                ]
            ],

            "DISBURSE_INQUIRY" => [
                "algo" => "sha256",
                "fields" => ["email", "time", "disburseId", "secretKey"]
            ],
        ];

        if (!isset($patterns[$type])) {
            return "";
        }

        $cfg = $patterns[$type];

        // Build string
        $base = "";
        foreach ($cfg["fields"] as $field) {
            $base .= (${$field} ?? $$field ?? "");
        }

        // MD5
        if ($cfg["algo"] === "md5") {
            return md5($base);
        }

        // HMAC
        if ($cfg["algo"] === "hmac") {
            $key = $this->{$cfg["key"]};
            return hash_hmac($cfg["hash"], $base, $key);
        }

        // SHA256
        return hash("sha256", $base);
    }
}
