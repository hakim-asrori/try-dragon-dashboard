@extends('layouts.admin.app')

@section('title',\App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value??'Dashboard')
@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @if(auth('admin')->user()->role_id == 1)
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="page--header-title">
                    <h1 class="page-header-title">{{translate('messages.welcome')}}, {{auth('admin')->user()->f_name}}.</h1>
                    <p class="page-header-text">{{translate('messages.Hello,_here_you_can_manage_your_orders_by_zone.')}}</p>
                </div>

                <div class="page--header-select">
                    <select name="zone_id" class="form-control js-select2-custom fetch-data-zone-wise">
                        <option value="all">{{translate('all_zones')}}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $zone)
                            <option
                                value="{{$zone['id']}}" {{$params['zone_id'] == $zone['id']?'selected':''}}>
                                {{$zone['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <!-- End Page Header -->


        <!-- Stats -->
        <div class="card mb-3">
            <div class="card-body pt-0">
                <div id="order_stats_top">
                    @include('admin-views.partials._order-statics',['data'=>$data])
                </div>
                <div class="row g-2 mt-2" id="order_stats">
                    @include('admin-views.partials._dashboard-order-stats',['data'=>$data])
                </div>
            </div>
        </div>

        <!-- End Stats -->
        <div class="row">
            <div class="col-lg-12">
                <!-- Card -->
                <div class="card h-100" id="monthly-earning-graph">
                    <!-- Body -->

                @include('admin-views.partials._monthly-earning-graph',['total_sell'=>$total_sell,'total_subs' =>$total_subs,'commission'=>$commission])
                <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>
        </div>
        <!-- End Row -->

        <div class="row g-2 mt-2">
            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100">
                    <!-- Header -->
                    <div class="card-header align-items-center">
                        <h5 class="card-title">
                            <img src="{{dynamicAsset('/public/assets/admin/img/dashboard/statistics.png')}}" alt="dashboard" class="card-header-icon">
                            <span>{{translate('user_statistics')}}</span>
                        </h5>
                        <div id="stat_zone">
                            @include('admin-views.partials._zone-change',['data'=>$data])
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body">
                        <div class="row justify-content-end">
                            <div class="col-sm-6 col-md-4">
                                <div class="ml-auto mb-2 pb-xl-5">
                                <select class="custom-select user-overview-stats-update" name="user_overview">
                                    <option
                                        value="overall" {{$params['user_overview'] == 'overall'?'selected':''}}>
                                        {{translate('messages.Overall')}}
                                    </option>

                                    <option
                                        value="this_year" {{$params['user_overview'] == 'this_year'?'selected':''}}>
                                        {{translate('This_Year')}}
                                    </option>
                                    <option
                                        value="this_month" {{$params['user_overview'] == 'this_month'?'selected':''}}>
                                        {{translate('This_Month')}}
                                    </option>
                                    <option
                                        value="this_week" {{$params['user_overview'] == 'this_week'?'selected':''}}>
                                        {{translate('This_Week')}}
                                    </option>

                                    <option
                                    value="today" {{$params['user_overview'] == 'today'?'selected':''}}>
                                    {{translate('Today')}}
                                </option>
                                </select>
                            </div>
                            </div>
                        </div>
                        <div class="position-relative" >
                            <div id="user-overview-board">
                                @php($params = session('dash_params'))
                                @if ($params['zone_id'] != 'all')
                                    @php($zone_name = \App\Models\Zone::where('id', $params['zone_id'])->first()->name)
                                @else
                                @php($zone_name=translate('All'))
                                @endif
                                <div class="chartjs-custom mx-auto">
                                    <div data-id="#user-overview" data-value="{{ $data['customer'].','. $data['restaurants'].','. $data['delivery_man'] }}"
                                    data-labels="{{translate('messages.Customer')}}, {{translate('messages.Restaurant')}},{{ translate('messages.Delivery_man') }}" id="user-overview"></div>
                                </div>
                            </div>
                        </div>

                        <!-- End Chart -->
                    </div>
                    <!-- End Body -->
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100" id="popular-restaurants-view">
                    @include('admin-views.partials._popular-restaurants',['popular'=>$data['popular']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100" id="top-deliveryman-view">
                    @include('admin-views.partials._top-deliveryman',['top_deliveryman'=>$data['top_deliveryman']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100" id="top-restaurants-view">
                    @include('admin-views.partials._top-restaurants',['top_restaurants'=>$data['top_restaurants']])
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100" id="top-rated-foods-view">
                    @include('admin-views.partials._top-rated-foods',['top_rated_foods'=>$data['top_rated_foods']])
                </div>
                <!-- End Card -->
            </div>


            <div class="col-lg-6">
                <!-- Card -->
                <div class="card h-100" id="top-selling-foods-view">
                    @include('admin-views.partials._top-selling-foods',['top_sell'=>$data['top_sell']])
                </div>
                <!-- End Card -->
            </div>
        </div>
        @else
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('messages.welcome')}}, {{auth('admin')->user()->f_name}}.</h1>
                    <p class="page-header-text">{{translate('messages.Hello,_here_you_can_manage_your_restaurants.')}}</p>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        @endif
    </div>
@endsection

@push('script')
<script src="{{dynamicAsset('public/assets/admin/apexcharts/apexcharts.min.js')}}"></script>
@endpush

@push('script_2')
<script src="{{dynamicAsset('public/assets/admin/js/view-pages/apex-charts.js')}}"></script>
<script>

    "use strict";
    loadchart();
    function loadchart(){
        var id = $('#user-overview').data('id');
        var value = $('#user-overview').data('value').split(',').map(Number);
        var labels = $('#user-overview').data('labels').split(',');

        var commission = $('#updatingData').data('commission').split(',').map(Number);
        var subscription = $('#updatingData').data('subscription').split(',').map(Number);
        var total_sell = $('#updatingData').data('total_sell').split(',').map(Number);

        newdonutChart(id,value,labels)

        var options = {
            series: [{
                name: '{{ translate('messages.admin_commission') }}',
                data: commission,
                },  {
                name: '{{ translate('messages.total_sell') }}',
                data: total_sell,
                }
                @if (\App\CentralLogics\Helpers::subscription_check())
                ,{
                name: '{{ translate('messages.Subscription') }}',
                data: subscription,
                }
                @endif

                ],
            chart: {
                    toolbar:{
                        show: false
                    },
                type: 'bar',
                height: 380
            },
            plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 0,
                borderRadiusApplication: 'around',
                borderRadiusWhenStacked: 'last',
                columnWidth: '70%',
                barHeight: '70%',
                distributed: false,
                rangeBarOverlap: true,
                rangeBarGroupRows: false,
                hideZeroBarsWhenGrouped: false,
                isDumbbell: false,
                dumbbellColors: undefined,
                isFunnel: false,
                isFunnel3d: true,
                    colors: {
                        ranges: [{
                            from: 0,
                            to: 0,
                            color: undefined
                        }],
                        backgroundBarColors: [],
                        backgroundBarOpacity: 1,
                        backgroundBarRadius: 0,
                    }
                }
            },
            dataLabels: {
            enabled: false,
                position: 'top',
                maxItems: 1,
                hideOverflowingLabels: true,
                    total: {
                    enabled: false,
                    formatter: undefined,
                    offsetX: 0,
                    offsetY: 0,
                        style: {
                            color: '#373d3f',
                            fontSize: '12px',
                            fontFamily: undefined,
                            fontWeight: 600
                        }
                    }
            },
            stroke: {
                show: true,
                curve: 'smooth',
                lineCap: 'butt',
                width: 2,
                dashArray: 0,
                colors: ['transparent']
            },
            xaxis: {
                categories: ["{{ translate('messages.Jan') }}","{{ translate('messages.Feb') }}","{{ translate('messages.Mar') }}","{{ translate('messages.April') }}","{{ translate('messages.May') }}","{{ translate('messages.Jun') }}","{{ translate('messages.Jul') }}","{{ translate('messages.Aug') }}","{{ translate('messages.Sep') }}","{{ translate('messages.Oct') }}","{{ translate('messages.Nov') }}","{{ translate('messages.Dec') }}"],
            },
            yaxis: {
                title: {
                    text: '{{ \App\CentralLogics\Helpers::currency_symbol() }}. ({{ \App\CentralLogics\Helpers::currency_code() }})'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "{{ \App\CentralLogics\Helpers::currency_symbol() }} " + val + " {{ \App\CentralLogics\Helpers::currency_code() }}"
                    }
                }
            }
        };

        var commissionchart = new ApexCharts(document.querySelector("#updatingData"), options);
        commissionchart.render();
    }
    $(document).on('change', '.order-stats-update', function () {
        let type = $(this).val();
            order_stats_update(type);
        });


        function order_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.order')}}',
                data: {
                    statistics_type: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('statistics_type',type);
                    $('#order_stats').html(data.view)
                    $('#order_stats_top').html(data.order_stats_top)
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.fetch-data-zone-wise').on('change',function (){
            let zone_id = $(this).val();
            fetch_data_zone_wise(zone_id)
        })

        function fetch_data_zone_wise(zone_id) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.zone')}}',
                data: {
                    zone_id: zone_id
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('zone_id', zone_id);
                    $('#order_stats_top').html(data.order_stats_top);
                    $('#order_stats').html(data.order_stats);
                    $('#stat_zone').html(data.stat_zone);
                    $('#user-overview-board').html(data.user_overview);
                    $('#monthly-earning-graph').html(data.monthly_graph);
                    $('#popular-restaurants-view').html(data.popular_restaurants);
                    $('#top-deliveryman-view').html(data.top_deliveryman);
                    $('#top-rated-foods-view').html(data.top_rated_foods);
                    $('#top-restaurants-view').html(data.top_restaurants);
                    $('#top-selling-foods-view').html(data.top_selling_foods);
                    loadchart();
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        $('.user-overview-stats-update').on('change',function (){
            let type = $(this).val();
            user_overview_stats_update(type)
        })

        function user_overview_stats_update(type) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.user-overview')}}',
                data: {
                    user_overview: type
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    insert_param('user_overview',type);
                    $('#user-overview-board').html(data.view)
                    loadchart();
                },
                complete: function () {
                    $('#loading').hide()
                }
            });
        }

        function insert_param(key, value) {
            key = encodeURIComponent(key);
            value = encodeURIComponent(value);
            let kvp = document.location.search.substr(1).split('&');
            let i = 0;

            for (; i < kvp.length; i++) {
                if (kvp[i].startsWith(key + '=')) {
                    let pair = kvp[i].split('=');
                    pair[1] = value;
                    kvp[i] = pair.join('=');
                    break;
                }
            }
            if (i >= kvp.length) {
                kvp[kvp.length] = [key, value].join('=');
            }
            // can return this or...
            let params = kvp.join('&');
            // change url page with new params
            window.history.pushState('page2', 'Title', '{{url()->current()}}?' + params);
        }
    </script>
@endpush
