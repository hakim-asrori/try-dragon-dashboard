<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTopup extends Model
{
    use HasFactory, HasUuids;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    protected $guarded = [];

    public function topupable()
    {
        return $this->morphTo();
    }

    public function paymentRequest()
    {
        return $this->hasOne(PaymentRequest::class, 'attribute_id', 'id')->where('attribute', 'wallet_topups');
    }
}
