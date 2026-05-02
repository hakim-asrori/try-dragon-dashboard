@extends('layouts.admin.app')

@section('title', translate('messages.Payment_Requests'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">

        <div class="page-header">
            <h1 class="page-header-title mb-2 text-capitalize">
                <span>{{ translate('messages.Payment_Requests') }}</span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h3 class="card-title">
                        <span>{{ translate('messages.Payment_Request_List') }}</span>
                        <span class="badge badge-soft-secondary" id="itemCount">{{ count($paymentRequests) }}</span>
                    </h3>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.attribute_id') }}</th>
                                <th>{{ translate('messages.attribute') }}</th>
                                <th>{{ translate('messages.transaction_id') }}</th>
                                <th>{{ translate('messages.payer_id') }}</th>
                                <th>{{ translate('messages.payment_method') }}</th>
                                <th>{{ translate('messages.amount') }}</th>
                                <th>{{ translate('messages.currency') }}</th>
                                <th>{{ translate('messages.status') }}</th>
                                <th>{{ translate('messages.date') }}</th>
                                <th class="text-center w-120px">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentRequests as $k => $pr)
                                <tr>
                                    <td>{{ $pr->attribute_id ?? translate('messages.N/A') }}</td>
                                    <td><span class="text-capitalize">{{ $pr->attribute ?? translate('messages.N/A') }}</span></td>
                                    <td>
                                        <span
                                            class="text-monospace">{{ $pr->transaction_id ?? translate('messages.N/A') }}</span>
                                    </td>
                                    <td>{{ $pr->payer_id ?? translate('messages.N/A') }}</td>
                                    <td>
                                        <span
                                            class="text-capitalize">{{ $pr->payment_method ?? translate('messages.N/A') }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ \App\CentralLogics\Helpers::format_currency($pr->payment_amount) }}</strong>
                                    </td>
                                    <td>{{ strtoupper($pr->currency_code) }}</td>
                                    <td>
                                        @if ($pr->is_paid)
                                            <label
                                                class="badge badge-soft-success rounded-pill">{{ translate('messages.paid') }}</label>
                                        @else
                                            <label
                                                class="badge badge-soft-warning rounded-pill">{{ translate('messages.unpaid') }}</label>
                                        @endif
                                    </td>
                                    <td>{{ \App\CentralLogics\Helpers::time_date_format($pr->created_at) }}</td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <button type="button"
                                                class="btn action-btn btn--primary btn-outline-primary detail-btn"
                                                data-toggle="modal" data-target="#detailModal"
                                                data-id="{{ $pr->id }}" data-payer_id="{{ $pr->payer_id }}"
                                                data-receiver_id="{{ $pr->receiver_id }}"
                                                data-transaction_id="{{ $pr->transaction_id }}"
                                                data-payment_method="{{ $pr->payment_method }}"
                                                data-payment_platform="{{ $pr->payment_platform }}"
                                                data-amount="{{ \App\CentralLogics\Helpers::format_currency($pr->payment_amount) }}"
                                                data-currency="{{ strtoupper($pr->currency_code) }}"
                                                data-is_paid="{{ $pr->is_paid }}" data-attribute="{{ $pr->attribute }}"
                                                data-attribute_id="{{ $pr->attribute_id }}"
                                                data-payer_information="{{ $pr->payer_information }}"
                                                data-created_at="{{ \App\CentralLogics\Helpers::time_date_format($pr->created_at) }}"
                                                data-updated_at="{{ \App\CentralLogics\Helpers::time_date_format($pr->updated_at) }}"
                                                data-request_data="{{ $pr->request_data }}"
                                                data-response_data="{{ $pr->response_data }}"
                                                data-callback_data="{{ $pr->callback_data }}">
                                                <i class="tio-visible"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if (count($paymentRequests) === 0)
                        <div class="empty--data">
                            <img src="{{ dynamicAsset('/public/assets/admin/img/empty.png') }}" alt="public">
                            <h5>{{ translate('messages.no_data_found') }}</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">{{ translate('messages.Payment_Request_Detail') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"
                        aria-label="{{ translate('messages.close') }}">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header py-2">
                                    <h6 class="mb-0">{{ translate('messages.transaction_info') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="key-val-list d-flex flex-column gap-2">
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.transaction_id') }}:</span>
                                            <span id="modal_transaction_id"
                                                class="text-dark font-weight-bold text-monospace fs-12"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.payment_method') }}:</span>
                                            <span id="modal_payment_method" class="text-capitalize"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.platform') }}:</span>
                                            <span id="modal_payment_platform" class="text-capitalize"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="text-muted min-w-120px">{{ translate('messages.amount') }}:</span>
                                            <span id="modal_amount" class="text-primary font-weight-bold"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.currency') }}:</span>
                                            <span id="modal_currency"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="text-muted min-w-120px">{{ translate('messages.status') }}:</span>
                                            <span id="modal_status"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.attribute') }}:</span>
                                            <span id="modal_attribute" class="text-capitalize"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.attribute_id') }}:</span>
                                            <span id="modal_attribute_id" class="fs-12"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.created_at') }}:</span>
                                            <span id="modal_created_at"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header py-2">
                                    <h6 class="mb-0">{{ translate('messages.payer_info') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="key-val-list d-flex flex-column gap-2">
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.payer_id') }}:</span>
                                            <span id="modal_payer_id" class="fs-12"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="text-muted min-w-120px">{{ translate('messages.name') }}:</span>
                                            <span id="modal_payer_name"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="text-muted min-w-120px">{{ translate('messages.email') }}:</span>
                                            <span id="modal_payer_email"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="text-muted min-w-120px">{{ translate('messages.phone') }}:</span>
                                            <span id="modal_payer_phone"></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span
                                                class="text-muted min-w-120px">{{ translate('messages.receiver_id') }}:</span>
                                            <span id="modal_receiver_id" class="fs-12"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header py-2">
                                    <h6 class="mb-0">{{ translate('messages.raw_data') }}</h6>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="nav nav-tabs" id="rawDataTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="request-tab" data-toggle="tab"
                                                href="#tab-request" role="tab">
                                                {{ translate('messages.request_data') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="response-tab" data-toggle="tab" href="#tab-response"
                                                role="tab">
                                                {{ translate('messages.response_data') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="callback-tab" data-toggle="tab" href="#tab-callback"
                                                role="tab">
                                                {{ translate('messages.callback_data') }}
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content p-3">
                                        <div class="tab-pane fade show active" id="tab-request" role="tabpanel">
                                            <pre id="modal_request_data" class="bg-light p-3 rounded fs-12 mb-0"
                                                style="max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
                                        </div>
                                        <div class="tab-pane fade" id="tab-response" role="tabpanel">
                                            <pre id="modal_response_data" class="bg-light p-3 rounded fs-12 mb-0"
                                                style="max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
                                        </div>
                                        <div class="tab-pane fade" id="tab-callback" role="tabpanel">
                                            <pre id="modal_callback_data" class="bg-light p-3 rounded fs-12 mb-0"
                                                style="max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--reset"
                        data-dismiss="modal">{{ translate('messages.close') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";

        $('.detail-btn').on('click', function() {
            let data = $(this).data();

            $('#modal_transaction_id').text(data.transaction_id || '-');
            $('#modal_payment_method').text(data.payment_method || '-');
            $('#modal_payment_platform').text(data.payment_platform || '-');
            $('#modal_amount').text(data.amount || '-');
            $('#modal_currency').text(data.currency || '-');
            $('#modal_attribute').text(data.attribute || '-');
            $('#modal_attribute_id').text(data.attribute_id || '-');
            $('#modal_payer_id').text(data.payer_id || '-');
            $('#modal_receiver_id').text(data.receiver_id || '-');
            $('#modal_created_at').text(data.created_at || '-');

            if (data.is_paid == 1) {
                $('#modal_status').html(
                    '<label class="badge badge-soft-success rounded-pill">{{ translate('messages.paid') }}</label>'
                    );
            } else {
                $('#modal_status').html(
                    '<label class="badge badge-soft-warning rounded-pill">{{ translate('messages.unpaid') }}</label>'
                    );
            }

            try {
                let payer = typeof data.payer_information === 'string' ?
                    JSON.parse(data.payer_information) :
                    data.payer_information;
                $('#modal_payer_name').text(payer?.name || '-');
                $('#modal_payer_email').text(payer?.email || '-');
                $('#modal_payer_phone').text(payer?.phone || '-');
            } catch (e) {
                $('#modal_payer_name').text('-');
                $('#modal_payer_email').text('-');
                $('#modal_payer_phone').text('-');
            }

            function prettyJson(val) {
                try {
                    let parsed = typeof val === 'string' ? JSON.parse(val) : val;
                    return JSON.stringify(parsed, null, 2);
                } catch (e) {
                    return val || '-';
                }
            }

            $('#modal_request_data').text(prettyJson(data.request_data));
            $('#modal_response_data').text(prettyJson(data.response_data));
            $('#modal_callback_data').text(prettyJson(data.callback_data));

            $('#rawDataTabs a:first').tab('show');
        });
    </script>
@endpush
