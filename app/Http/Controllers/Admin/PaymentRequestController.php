<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;

class PaymentRequestController extends Controller
{
    public function __invoke()
    {
        $paymentRequests = PaymentRequest::all();

        return view('admin-views.payment-request.index', compact('paymentRequests'));
    }
}
