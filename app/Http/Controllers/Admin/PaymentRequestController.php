<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;

class PaymentRequestController extends Controller
{
    public function __invoke(Request $request)
    {
        $paymentRequests = PaymentRequest::when($request->search, function ($q) use ($request) {
                $q->where('transaction_id', 'like', "%{$request->search}%")
                  ->orWhere('attribute_id', 'like', "%{$request->search}%")
                  ->orWhere('payer_id', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate(config('default_pagination'));

        return view('admin-views.payment-request.index', compact('paymentRequests'));
    }
}
