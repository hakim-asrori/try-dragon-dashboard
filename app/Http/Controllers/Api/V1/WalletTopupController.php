<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Validator};

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Library\{Payer, Payment as PaymentInfo, Receiver};
use App\Library\PG\Calculator;
use App\Models\{BusinessSetting, DeliveryMan, WalletTopup};
use App\Traits\Payment;

class WalletTopupController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'          => 'required|numeric|min:1',
            'payment_gateway' => 'required|string',
            'callback'        => 'required|string',
            'token'           => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request->token])->first();
        if (!$dm) {
            return response()->json([
                'errors' => [
                    ['code' => 'deliveryman', 'message' => translate('messages.deliveryman_not_found')]
                ]
            ], 404);
        }

        $digitalPayment = Helpers::get_business_settings('digital_payment');
        if (($digitalPayment['status'] ?? 0) == 0) {
            return response()->json([
                'errors' => [
                    ['code' => 'digital_payment', 'message' => 'digital_payment_is_disable']
                ]
            ], 403);
        }

        $pgConfig = $this->resolvePgConfig($request->payment_gateway);
        if (!$pgConfig) {
            return response()->json([
                'errors' => [
                    ['code' => 'payment_gateway', 'message' => translate('messages.payment_gateway_not_found')]
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $calc = Calculator::calculateTransaction((float) $request->amount, $pgConfig);

            $walletTopup = WalletTopup::create([
                'topupable_type'        => DeliveryMan::class,
                'topupable_id'          => $dm->id,
                'amount'                => $calc->amount,
                'vendor_fee_amount'     => $pgConfig['vendor_fee_amount'] ?? 0,
                'vendor_fee_percentage' => $pgConfig['vendor_fee_percentage'] ?? 0,
                'surcharge_amount'      => $pgConfig['surcharge_amount'] ?? 0,
                'surcharge_percentage'  => $pgConfig['surcharge_percentage'] ?? 0,
                'total_fee'             => $calc->total_fee_amount,
                'net_amount'            => $calc->settled_amount,
                'fee_charge_to'         => strtolower($calc->charge_to),
                'ip_address'            => $request->ip(),
                'agent'                 => $request->userAgent(),
                'status'                => WalletTopup::STATUS_PENDING,
            ]);

            $payer = new Payer($dm->f_name, $dm->email, $dm->phone, '');

            $additional_data = [
                'business_name'   => BusinessSetting::where(['key' => 'business_name'])->first()?->value,
                'business_logo'   => dynamicStorage('storage/app/public/business') . '/' . BusinessSetting::where(['key' => 'logo'])->first()?->value,
                'wallet_topup_id' => $walletTopup->id,
            ];

            $payment_info = new PaymentInfo(
                success_hook: 'wallet_topup_success',
                failure_hook: 'wallet_topup_fail',
                currency_code: Helpers::currency_code(),
                payment_method: $request->payment_gateway,
                payment_platform: 'app',
                payer_id: $dm->id,
                receiver_id: '100',
                additional_data: $additional_data,
                payment_amount: $calc->customer_pays,
                external_redirect_link: $request->callback,
                attribute: 'wallet_topups',
                attribute_id: $walletTopup->id,
            );

            $receiver_info = new Receiver('Admin', 'example.png');
            $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

            DB::commit();

            return response()->json([
                'redirect_link' => $redirect_link,
                'topup_id'      => $walletTopup->id,
                'amount'        => $calc->amount,
                'fee_breakdown' => [
                    'vendor_fee'     => $calc->vendor_fee_amount,
                    'surcharge_fee'  => $calc->surcharge_fee_amount,
                    'total_fee'      => $calc->total_fee_amount,
                    'fee_charged_to' => strtolower($calc->charge_to),
                ],
                'customer_pays' => $calc->customer_pays,
                'net_amount'    => $calc->settled_amount,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    ['code' => 'amount_validation', 'message' => $e->getMessage()]
                ]
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return response()->json([
                'errors' => [
                    ['code' => 'wallet_topup_problem', 'message' => translate('messages.wallet_topup_problem')]
                ]
            ], 500);
        }
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'           => 'required|string',
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $paginator = WalletTopup::with('paymentRequest:response_data,attribute_id,attribute')->where('topupable_type', DeliveryMan::class)
            ->where('topupable_id', $dm->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request['limit'], ['id', 'amount', 'net_amount', 'total_fee', 'status', 'paid_at', 'failed_at', 'expired_at', 'cancelled_at', 'settled_at', 'created_at', 'updated_at'], 'page', $request['offset']);

        $paginator->getCollection()->transform(function ($walletTopup) {
            return [
                ...$walletTopup->toArray(),
                'payment_request' => [
                    "attribute" => $walletTopup->paymentRequest->attribute,
                    "attribute_id" => $walletTopup->paymentRequest->attribute_id,
                    "payment_url" => $walletTopup->paymentRequest->response_data ?  json_decode($walletTopup->paymentRequest->response_data)->paymentUrl : "",
                ]
            ];
        });

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'data' => $paginator->items()
        ];

        return response()->json($data, 200);
    }

    public function details(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $walletTopup = WalletTopup::with('paymentRequest:response_data,attribute_id,attribute')->where('topupable_type', DeliveryMan::class)
            ->where('topupable_id', $dm->id)
            ->where('id', $id)
            ->first();

        return response()->json([
            'id'            => $walletTopup->id,
            'amount'        => $walletTopup->amount,
            'net_amount'    => $walletTopup->net_amount,
            'total_fee'     => $walletTopup->total_fee,
            'status'        => $walletTopup->status,
            'paid_at'       => $walletTopup->paid_at,
            'failed_at'     => $walletTopup->failed_at,
            'expired_at'    => $walletTopup->expired_at,
            'cancelled_at'  => $walletTopup->cancelled_at,
            'settled_at'    => $walletTopup->settled_at,
            'created_at'    => $walletTopup->created_at,
            'updated_at'    => $walletTopup->updated_at,
            'payment_request' => [
                "attribute" => $walletTopup->paymentRequest->attribute,
                "attribute_id" => $walletTopup->paymentRequest->attribute_id,
                "payment_url" => json_decode($walletTopup->paymentRequest->response_data)->paymentUrl,
            ]
        ], 200);
    }

    private function resolvePgConfig(string $gateway): ?array
    {
        $config = DB::table('addon_settings')
            ->where('key_name', $gateway)
            ->where('settings_type', 'payment_config')
            ->first();

        if (!$config) {
            return null;
        }

        $raw = $config->mode === 'live' ? $config->live_values : $config->test_values;
        return json_decode($raw, true) ?: null;
    }
}
