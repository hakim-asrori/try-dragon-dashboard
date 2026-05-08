<?php

namespace App\Library\PG;

use Illuminate\Support\Number;

class Calculator
{
    public static function calculateTransaction(
        float $amount,
        ?array $pgData,
    ): object {

        $pgData = (object) $pgData;

        if ($amount < (float) $pgData->trx_min_amount) {
            throw new \InvalidArgumentException(
                "Amount is below the minimum allowed: " . Number::currency($pgData->trx_min_amount, 'IDR', 'id')
            );
        }

        if ($amount > (float) $pgData->trx_max_amount) {
            throw new \InvalidArgumentException(
                "Amount exceeds the maximum allowed: " . Number::currency($pgData->trx_max_amount, 'IDR', 'id')
            );
        }

        $baseAmount = $amount;

        $vendorFee = 0;
        $vendorFeeInfo = 0;
        if ($pgData->vendor_fee_amount > 0) {
            $vendorFee = (float) $pgData->vendor_fee_amount;
            $vendorFeeInfo = $vendorFee;
        } elseif ($pgData->vendor_fee_percentage > 0) {
            $vendorFee = $baseAmount * ((float) $pgData->vendor_fee_percentage / 100);
            $vendorFeeInfo = (float) $pgData->vendor_fee_percentage;
        }

        $surchargeFee = 0;
        $surchargeFeeInfo = 0;
        if ($pgData->surcharge_amount > 0) {
            $surchargeFee = (float) $pgData->surcharge_amount;
            $surchargeFeeInfo = $surchargeFee;
        } elseif ($pgData->surcharge_percentage > 0) {
            $surchargeFee = $baseAmount * ((float) $pgData->surcharge_percentage / 100);
            $surchargeFeeInfo = (float) $pgData->surcharge_percentage;
        }

        $vendorChargeTo = strtoupper($pgData->charge_to ?? 'customer');

        $customerPays = $baseAmount;

        if ($vendorChargeTo === 'customer') {
            $customerPays += $vendorFee + $surchargeFee;
            $settledAmount = $baseAmount + $surchargeFee;
        } else {
            $customerPays = $baseAmount;
            $settledAmount = $baseAmount - $vendorFee;
        }

        return (object)  [
            'amount' => $baseAmount,

            'vendor_fee_info'           => $vendorFeeInfo,
            'vendor_fee_amount'         => $vendorFee,

            'surcharge_fee_info'        => $surchargeFeeInfo,
            'surcharge_fee_amount'      => $surchargeFee,

            'customer_pays'             => $customerPays,
            'total_fee_amount'          => $vendorFee + $surchargeFee,
            'settled_amount'            => $settledAmount,

            'charge_to'                 => $vendorChargeTo,
        ];
    }
}
