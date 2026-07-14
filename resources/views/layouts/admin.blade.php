<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ trans('panel.site_title') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/1.2.4/css/buttons.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/css/AdminLTE.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/css/skins/_all-skins.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet" />
    <style>
        .sidebar-menu > li:first-child {
            padding: 10px;
        }

        .sidebar .select2-container--default .select2-selection--single {
            background: rgba(255, 255, 255, 0.96);
            border: 0;
            border-radius: 6px;
            height: 38px;
            position: relative;
        }

        .sidebar .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #555;
            line-height: 38px;
            padding-left: 38px;
        }

        .sidebar .select2-container--default .select2-selection--single .select2-selection__rendered::before {
            color: #605ca8;
            content: "\f002";
            font-family: FontAwesome;
            left: 13px;
            position: absolute;
        }

        .sidebar .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
            right: 6px;
        }

        .global-search-dropdown.select2-dropdown {
            border: 0;
            border-radius: 6px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
            min-width: 380px;
            overflow: hidden;
        }

        .global-search-dropdown .select2-search--dropdown {
            background: #f7f7fb;
            border-bottom: 1px solid #ececf2;
            padding: 10px;
        }

        .global-search-dropdown .select2-search__field {
            border: 1px solid #d9d9e3 !important;
            border-radius: 5px;
            height: 38px;
            outline: none;
            padding: 6px 10px;
        }

        .global-search-dropdown .select2-search__field:focus {
            border-color: #605ca8 !important;
            box-shadow: 0 0 0 3px rgba(96, 92, 168, 0.12);
        }

        .global-search-dropdown .select2-results__options {
            max-height: 360px;
        }

        .global-search-dropdown .select2-results__option {
            border-bottom: 1px solid #f0f0f4;
            padding: 10px 12px;
        }

        .global-search-dropdown .select2-results__option:last-child {
            border-bottom: 0;
        }

        .global-search-dropdown .select2-results__option[aria-selected="true"] {
            background: #605ca8;
        }

        .global-search-dropdown .select2-results__option[aria-selected="true"] .global-search-result-type,
        .global-search-dropdown .select2-results__option[aria-selected="true"] .global-search-result-title,
        .global-search-dropdown .select2-results__option[aria-selected="true"] .global-search-result-detail {
            color: #fff;
        }

        .global-search-dropdown .select2-results__option--highlighted[aria-selected] {
            background: #4b468c;
        }

        .global-search-dropdown .select2-results__option--highlighted .global-search-result-type,
        .global-search-dropdown .select2-results__option--highlighted .global-search-result-title,
        .global-search-dropdown .select2-results__option--highlighted .global-search-result-detail {
            color: #fff;
        }

        .global-search-dropdown .select2-results__message {
            color: #777;
            font-size: 13px;
            padding: 16px;
            text-align: center;
        }

        .global-search-result-type {
            color: #605ca8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .global-search-result-title {
            color: #333;
            font-weight: 700;
            margin: 2px 0 4px;
        }

        .global-search-result-detail {
            color: #666;
            font-size: 12px;
            line-height: 1.5;
        }

        .global-search-result-link,
        .global-search-result-link:hover,
        .global-search-result-link:focus {
            color: inherit;
            display: block;
            outline: none;
            text-decoration: none;
        }

        @media (max-width: 767px) {
            .global-search-dropdown {
                min-width: calc(100vw - 30px);
            }
        }
    </style>
    @yield('styles')
</head>

<body class="sidebar-mini skin-purple" style="height: auto; min-height: 100%;">
    <div class="wrapper" style="height: auto; min-height: 100%;">
        <header class="main-header">
            <a href="#" class="logo">
                <span class="logo-mini"><b>{{ trans('panel.site_title') }}</b></span>
                <span class="logo-lg">{{ trans('panel.site_title') }}</span>
            </a>

            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
                    <span class="sr-only">{{ trans('global.toggleNavigation') }}</span>
                </a>

                @php
                    $calendarAlertTasks = collect();
                    $uncheckedStateTransfers = collect();
                    $pendingTradeIns = collect();
                    $pendingStandCashApprovals = collect();
                    $pendingSaleClosureApprovals = collect();
                    $rolePreviewIsRealAdmin = \App\Support\RolePreview::isRealAdmin(auth()->user());
                    $rolePreviewActiveRole = $rolePreviewIsRealAdmin ? \App\Support\RolePreview::activeRole() : null;
                    $rolePreviewRoles = $rolePreviewIsRealAdmin
                        ? \App\Models\Role::orderBy('title')->get(['id', 'title'])
                        : collect();
                    $canConvertTradeIns = auth()->check()
                        && \Illuminate\Support\Facades\Gate::allows('vehicle_trade_in_convert');
                    $canValidateStandCash = auth()->check()
                        && \App\Support\RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Stand Adm']);
                    $canValidateSaleClosures = auth()->check()
                        && \App\Support\RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Stand Adm']);
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasTable('calendar_tasks')) {
                            $calendarAlertTasks = \App\Models\CalendarTask::query()
                                ->whereNull('completed_at')
                                ->visibleTo(auth()->user())
                                ->whereDate('due_date', '<=', now()->addDays(3)->toDateString())
                                ->orderBy('due_date')
                                ->limit(10)
                                ->get();
                        }

                        if (\Illuminate\Support\Facades\Schema::hasTable('vehicle_state_transfers')
                            && \Illuminate\Support\Facades\Schema::hasColumn('vehicle_state_transfers', 'checked_at')) {
                            $uncheckedStateTransfers = \App\Models\VehicleStateTransfer::with(['vehicle', 'from_general_state', 'to_general_state'])
                                ->whereNull('checked_at')
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get();
                        }

                        if ($canConvertTradeIns && \Illuminate\Support\Facades\Schema::hasTable('vehicle_trade_ins')) {
                            $pendingTradeIns = \App\Models\VehicleTradeIn::with(['sold_vehicle'])
                                ->where('status', \App\Models\VehicleTradeIn::STATUS_PENDING)
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get();
                        }

                        if ($canValidateStandCash && \Illuminate\Support\Facades\Schema::hasTable('stand_cash_payment_approvals')) {
                            $pendingStandCashApprovals = \App\Models\StandCashPaymentApproval::with(['payment', 'vehicle.brand', 'created_by'])
                                ->where('status', \App\Models\StandCashPaymentApproval::STATUS_PENDING)
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get();
                        }

                        if ($canValidateSaleClosures && \Illuminate\Support\Facades\Schema::hasTable('sale_closure_approvals')) {
                            $pendingSaleClosureApprovals = \App\Models\SaleClosureApproval::with(['vehicle.brand', 'closed_by'])
                                ->where('status', \App\Models\SaleClosureApproval::STATUS_PENDING)
                                ->orderByDesc('closed_at')
                                ->limit(10)
                                ->get();
                        }
                    } catch (\Throwable $exception) {
                        $calendarAlertTasks = collect();
                        $uncheckedStateTransfers = collect();
                        $pendingTradeIns = collect();
                        $pendingStandCashApprovals = collect();
                        $pendingSaleClosureApprovals = collect();
                    }
                @endphp

                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        @if($rolePreviewIsRealAdmin)
                            <li style="padding: 8px 8px 0 0;">
                                <div class="form-inline" style="white-space: nowrap;">
                                    <form method="POST" action="{{ route('admin.role-preview.store') }}" style="display:inline-block;">
                                        @csrf
                                        <select class="form-control input-sm" name="role_id" onchange="this.form.submit()" title="Testar como role">
                                            <option value="" disabled {{ $rolePreviewActiveRole ? '' : 'selected' }}>Role real</option>
                                            @foreach($rolePreviewRoles as $rolePreviewRole)
                                                <option value="{{ $rolePreviewRole->id }}" {{ optional($rolePreviewActiveRole)->id === $rolePreviewRole->id ? 'selected' : '' }}>
                                                    {{ $rolePreviewRole->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                    @if($rolePreviewActiveRole)
                                        <form method="POST" action="{{ route('admin.role-preview.destroy') }}" style="display:inline-block;">
                                            @csrf
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button class="btn btn-xs btn-default" type="submit">Reset</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endif
                        <li>
                            <a href="#" data-toggle="modal" data-target="#system-shutdown-modal" title="Desligar sistema" style="background:#dd4b39;color:#fff;">
                                <i class="fa fa-power-off"></i>
                            </a>
                        </li>
                        @if($canConvertTradeIns)
                            <li class="dropdown notifications-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Retomas pendentes">
                                    <i class="fa fa-exchange"></i>
                                    @if($pendingTradeIns->count())
                                        <span class="label label-warning">{{ $pendingTradeIns->count() }}</span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">
                                        {{ $pendingTradeIns->count() ? $pendingTradeIns->count() . ' retomas pendentes' : 'Sem retomas pendentes' }}
                                    </li>
                                    <li>
                                        <ul class="menu">
                                            @forelse($pendingTradeIns as $tradeIn)
                                                <li>
                                                    <a href="{{ route('admin.vehicle-trade-ins.index', ['status' => \App\Models\VehicleTradeIn::STATUS_PENDING]) }}">
                                                        <i class="fa fa-exchange text-yellow"></i>
                                                        {{ $tradeIn->license }} -
                                                        {{ $tradeIn->sold_vehicle_id ? 'venda ' . ($tradeIn->sold_vehicle->license ?? $tradeIn->sold_vehicle->foreign_license ?? ('#' . $tradeIn->sold_vehicle_id)) : 'sem venda associada' }}
                                                    </a>
                                                </li>
                                            @empty
                                                <li>
                                                    <a href="{{ route('admin.vehicle-trade-ins.index', ['status' => \App\Models\VehicleTradeIn::STATUS_PENDING]) }}">
                                                        <i class="fa fa-check text-green"></i> Sem retomas pendentes.
                                                    </a>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="{{ route('admin.vehicle-trade-ins.index', ['status' => \App\Models\VehicleTradeIn::STATUS_PENDING]) }}">Ver retomas pendentes</a></li>
                                </ul>
                            </li>
                        @endif
                        @if($canValidateSaleClosures)
                            <li class="dropdown notifications-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Fechos de venda por validar">
                                    <i class="fa fa-check-square-o"></i>
                                    @if($pendingSaleClosureApprovals->count())
                                        <span class="label label-danger">{{ $pendingSaleClosureApprovals->count() }}</span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">
                                        {{ $pendingSaleClosureApprovals->count() ? $pendingSaleClosureApprovals->count() . ' fechos de venda por validar' : 'Sem fechos de venda por validar' }}
                                    </li>
                                    <li>
                                        <ul class="menu">
                                            @forelse($pendingSaleClosureApprovals as $approval)
                                                <li>
                                                    <a href="{{ route('admin.sale-closure-approvals.index') }}">
                                                        <i class="fa fa-check-square-o text-red"></i>
                                                        {{ $approval->vehicle->license ?? $approval->vehicle->foreign_license ?? ('Viatura #' . $approval->vehicle_id) }}
                                                        - {{ number_format((float) $approval->sales_total, 2, ',', '.') }} EUR
                                                        <div class="text-muted small">Fechado por {{ $approval->closed_by->name ?? '-' }}</div>
                                                    </a>
                                                </li>
                                            @empty
                                                <li>
                                                    <a href="{{ route('admin.sale-closure-approvals.index') }}">
                                                        <i class="fa fa-check text-green"></i> Sem fechos de venda por validar.
                                                    </a>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="{{ route('admin.sale-closure-approvals.index') }}">Validar fechos de venda</a></li>
                                </ul>
                            </li>
                        @endif
                        @if($canValidateStandCash)
                            <li class="dropdown notifications-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Pagamentos do Stand por validar">
                                    <i class="fa fa-money"></i>
                                    @if($pendingStandCashApprovals->count())
                                        <span class="label label-danger">{{ $pendingStandCashApprovals->count() }}</span>
                                    @endif
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">
                                        {{ $pendingStandCashApprovals->count() ? $pendingStandCashApprovals->count() . ' pagamentos por validar' : 'Sem pagamentos por validar' }}
                                    </li>
                                    <li>
                                        <ul class="menu">
                                            @forelse($pendingStandCashApprovals as $approval)
                                                <li>
                                                    <a href="{{ route('admin.stand-cash-payment-approvals.index') }}">
                                                        <i class="fa fa-money text-red"></i>
                                                        {{ $approval->vehicle->license ?? $approval->vehicle->foreign_license ?? ('Viatura #' . $approval->vehicle_id) }}
                                                        - {{ number_format((float) optional($approval->payment)->amount, 2, ',', '.') }} EUR
                                                        <div class="text-muted small">Criado por {{ $approval->created_by->name ?? '-' }}</div>
                                                    </a>
                                                </li>
                                            @empty
                                                <li>
                                                    <a href="{{ route('admin.stand-cash-payment-approvals.index') }}">
                                                        <i class="fa fa-check text-green"></i> Sem pagamentos por validar.
                                                    </a>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="{{ route('admin.stand-cash-payment-approvals.index') }}">Validar pagamentos do Stand</a></li>
                                </ul>
                            </li>
                        @endif
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Mudancas de estado por verificar">
                                <i class="fa fa-circle-o"></i>
                                @if($uncheckedStateTransfers->count())
                                    <span class="label label-danger">{{ $uncheckedStateTransfers->count() }}</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header">
                                    {{ $uncheckedStateTransfers->count() ? $uncheckedStateTransfers->count() . ' mudancas de estado por verificar' : 'Sem mudancas por verificar' }}
                                </li>
                                <li>
                                    <ul class="menu">
                                        @forelse($uncheckedStateTransfers as $transfer)
                                            <li>
                                                <a href="{{ route('admin.vehicle-state-transfers.index') }}">
                                                    <i class="fa fa-circle text-red"></i>
                                                    {{ $transfer->vehicle->license ?? 'Viatura #' . $transfer->vehicle_id }}:
                                                    {{ $transfer->from_general_state->name ?? '-' }} &rarr; {{ $transfer->to_general_state->name ?? '-' }}
                                                </a>
                                            </li>
                                        @empty
                                            <li>
                                                <a href="{{ route('admin.vehicle-state-transfers.index') }}">
                                                    <i class="fa fa-check text-green"></i> Sem alertas de estados.
                                                </a>
                                            </li>
                                        @endforelse
                                    </ul>
                                </li>
                                <li class="footer"><a href="{{ route('admin.vehicle-state-transfers.index') }}">Ver historico de estados</a></li>
                            </ul>
                        </li>
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Tarefas do calendario">
                                <i class="fa fa-bell-o"></i>
                                @if($calendarAlertTasks->count())
                                    <span class="label label-warning">{{ $calendarAlertTasks->count() }}</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header">
                                    {{ $calendarAlertTasks->count() ? $calendarAlertTasks->count() . ' tarefas pendentes' : 'Sem tarefas pendentes' }}
                                </li>
                                <li>
                                    <ul class="menu">
                                        @forelse($calendarAlertTasks as $task)
                                            <li>
                                                <a href="{{ $task->target_url ?: route('admin.systemCalendar') . '#task-' . $task->id }}">
                                                    <i class="fa fa-calendar text-yellow"></i>
                                                    <strong>{{ $task->due_date }}</strong> - {{ $task->title }}
                                                </a>
                                            </li>
                                        @empty
                                            <li>
                                                <a href="{{ route('admin.systemCalendar') }}">
                                                    <i class="fa fa-check text-green"></i> Sem alertas de calendario.
                                                </a>
                                            </li>
                                        @endforelse
                                    </ul>
                                </li>
                                <li class="footer"><a href="{{ route('admin.systemCalendar') }}">Abrir calendario</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>

            </nav>
        </header>

        <div class="modal fade" id="system-shutdown-modal" tabindex="-1" role="dialog" aria-labelledby="system-shutdown-title">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.system-shutdown.store') }}" id="system-shutdown-form">
                        {{ csrf_field() }}
                        <div class="modal-header" style="background:#dd4b39;color:#fff;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title" id="system-shutdown-title">Desligar sistema</h4>
                        </div>
                        <div class="modal-body">
                            <p>Esta acao coloca o backoffice, API e app em baixo.</p>
                            <p>Para voltar a ligar sera necessario remover <code>storage/framework/down</code> no cPanel ou correr <code>php artisan up</code>.</p>
                            <div class="form-group">
                                <label for="system-shutdown-confirmation">Escreva DESLIGAR para confirmar</label>
                                <input type="text" class="form-control" name="confirmation" id="system-shutdown-confirmation" autocomplete="off">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger" id="system-shutdown-submit" disabled>Desligar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('partials.menu')

        <div class="content-wrapper" style="min-height: 960px;">
            @if(session('message'))
                <div class="row" style='padding:20px 20px 0 20px;'>
                    <div class="col-lg-12">
                        <div class="alert alert-success" role="alert">{{ session('message') }}</div>
                    </div>
                </div>
            @endif
            @php($defaultErrors = $errors->getBag('default'))
            @if($defaultErrors->count() > 0)
                <div class="row" style='padding:20px 20px 0 20px;'>
                    <div class="col-lg-12">
                        <div class="alert alert-danger">
                            <ul class="list-unstyled">
                                @foreach($defaultErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            @yield('content')
        </div>
        <footer class="main-footer text-center">
            <strong>{{ trans('panel.site_title') }} &copy;</strong> {{ trans('global.allRightsReserved') }}
        </footer>

        <form id="logoutform" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.3/js/adminlte.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script>
        $(function () {
            function formatNationalLicense(value) {
                var normalized = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                return normalized.length === 6
                    ? normalized.slice(0, 2) + '-' + normalized.slice(2, 4) + '-' + normalized.slice(4, 6)
                    : String(value || '').toUpperCase();
            }

            $(document).on('input blur', 'input[name="license"], input[name="trade_in_license"]', function () {
                var formatted = formatNationalLicense(this.value);
                if (formatted !== this.value) {
                    this.value = formatted;
                }
            });

            var $shutdownInput = $('#system-shutdown-confirmation');
            var $shutdownSubmit = $('#system-shutdown-submit');

            $shutdownInput.on('input', function () {
                $shutdownSubmit.prop('disabled', $shutdownInput.val() !== 'DESLIGAR');
            });

            $('#system-shutdown-modal').on('hidden.bs.modal', function () {
                $shutdownInput.val('');
                $shutdownSubmit.prop('disabled', true);
            });

            var $globalScrollbar = $('<div class="global-horizontal-scrollbar" aria-hidden="true"><div class="global-horizontal-scrollbar__content"></div></div>').appendTo('body');
            var $globalScrollbarContent = $globalScrollbar.children();
            var activeHorizontalContainer = null;
            var syncingHorizontalScroll = false;

            function prepareHorizontalTables() {
                $('.content-wrapper table.table').each(function () {
                    if (!$(this).closest('.table-responsive, .dataTables_scrollBody').length) {
                        $(this).wrap('<div class="table-responsive"></div>');
                    }
                });
            }

            prepareHorizontalTables();

            function horizontalCandidates() {
                return $('.content-wrapper .table-responsive, .content-wrapper .dataTables_scrollBody').filter(function () {
                    var rect = this.getBoundingClientRect();
                    return $(this).is(':visible')
                        && this.scrollWidth > this.clientWidth + 1
                        && rect.bottom > 0
                        && rect.top < window.innerHeight;
                });
            }

            function chooseHorizontalContainer() {
                var candidates = horizontalCandidates().get();
                if (!candidates.length) {
                    return null;
                }

                candidates.sort(function (first, second) {
                    var firstRect = first.getBoundingClientRect();
                    var secondRect = second.getBoundingClientRect();
                    var firstVisible = Math.min(firstRect.bottom, window.innerHeight) - Math.max(firstRect.top, 0);
                    var secondVisible = Math.min(secondRect.bottom, window.innerHeight) - Math.max(secondRect.top, 0);
                    return secondVisible - firstVisible;
                });

                return candidates[0];
            }

            function refreshGlobalHorizontalScrollbar(preferredContainer) {
                var container = preferredContainer && preferredContainer.scrollWidth > preferredContainer.clientWidth + 1
                    ? preferredContainer
                    : chooseHorizontalContainer();

                activeHorizontalContainer = container;
                if (!container) {
                    $globalScrollbar.removeClass('is-visible').scrollLeft(0);
                    $('body').removeClass('has-global-horizontal-scrollbar');
                    return;
                }

                $globalScrollbarContent.width(container.scrollWidth);
                $globalScrollbar.addClass('is-visible').scrollLeft(container.scrollLeft);
                $('body').addClass('has-global-horizontal-scrollbar');
            }

            $(document).on('mouseenter focusin', '.content-wrapper .table-responsive, .content-wrapper .dataTables_scrollBody', function () {
                refreshGlobalHorizontalScrollbar(this);
            });

            $(document).on('scroll', '.content-wrapper .table-responsive, .content-wrapper .dataTables_scrollBody', function () {
                if (this !== activeHorizontalContainer || syncingHorizontalScroll) {
                    return;
                }
                syncingHorizontalScroll = true;
                $globalScrollbar.scrollLeft(this.scrollLeft);
                syncingHorizontalScroll = false;
            });

            $globalScrollbar.on('scroll', function () {
                if (!activeHorizontalContainer || syncingHorizontalScroll) {
                    return;
                }
                syncingHorizontalScroll = true;
                activeHorizontalContainer.scrollLeft = this.scrollLeft;
                syncingHorizontalScroll = false;
            });

            $(window).on('resize scroll', function () {
                refreshGlobalHorizontalScrollbar();
            });

            $(document).on('draw.dt shown.bs.tab expanded.pushMenu collapsed.pushMenu', function () {
                window.setTimeout(function () {
                    prepareHorizontalTables();
                    refreshGlobalHorizontalScrollbar();
                }, 0);
            });

            var horizontalMutationTimer = null;
            var contentWrapper = document.querySelector('.content-wrapper');
            if (contentWrapper && window.MutationObserver) {
                new MutationObserver(function () {
                    window.clearTimeout(horizontalMutationTimer);
                    horizontalMutationTimer = window.setTimeout(function () {
                        prepareHorizontalTables();
                        refreshGlobalHorizontalScrollbar();
                    }, 50);
                }).observe(contentWrapper, { childList: true, subtree: true });
            }

            refreshGlobalHorizontalScrollbar();
        });

        $(function() {
  let copyButtonTrans = '{{ trans('global.datatables.copy') }}'
  let csvButtonTrans = '{{ trans('global.datatables.csv') }}'
  let excelButtonTrans = '{{ trans('global.datatables.excel') }}'
  let pdfButtonTrans = '{{ trans('global.datatables.pdf') }}'
  let printButtonTrans = '{{ trans('global.datatables.print') }}'
  let colvisButtonTrans = '{{ trans('global.datatables.colvis') }}'
  let selectAllButtonTrans = '{{ trans('global.select_all') }}'
  let selectNoneButtonTrans = '{{ trans('global.deselect_all') }}'

  $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, { className: 'btn' })
  $.extend(true, $.fn.dataTable.defaults, {
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Portuguese.json'
    },
    columnDefs: [{
        orderable: false,
        className: 'select-checkbox',
        targets: 0
    }, {
        orderable: false,
        searchable: false,
        targets: -1
    }],
    select: {
      style:    'multi+shift',
      selector: 'td:first-child'
    },
    order: [],
    scrollX: true,
    pageLength: 100,
    dom: 'lBfrtip<"actions">',
    buttons: [
      {
        extend: 'selectAll',
        className: 'btn-primary',
        text: selectAllButtonTrans,
        exportOptions: {
          columns: ':visible'
        },
        action: function(e, dt) {
          e.preventDefault()
          dt.rows().deselect();
          dt.rows({ search: 'applied' }).select();
        }
      },
      {
        extend: 'selectNone',
        className: 'btn-primary',
        text: selectNoneButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'copy',
        className: 'btn-default',
        text: copyButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'csv',
        className: 'btn-default',
        text: csvButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'excel',
        className: 'btn-default',
        text: excelButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'pdf',
        className: 'btn-default',
        text: pdfButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'print',
        className: 'btn-default',
        text: printButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      },
      {
        extend: 'colvis',
        className: 'btn-default',
        text: colvisButtonTrans,
        exportOptions: {
          columns: ':visible'
        }
      }
    ]
  });

  $.fn.dataTable.ext.classes.sPageButton = '';
});

    </script>
    <script>
        $(document).ready(function() {
    var globalSearchResultUrls = {};
    $('.searchable-field').select2({
        width: '100%',
        minimumInputLength: 3,
        dropdownCssClass: 'global-search-dropdown',
        ajax: {
            url: '{{ route("admin.globalSearch") }}',
            dataType: 'json',
            type: 'GET',
            delay: 200,
            data: function (term) {
                return {
                    search: term
                };
            },
            processResults: function (data) {
                $.each(data.results || [], function(index, result) {
                    globalSearchResultUrls[String(result.id)] = result.url;
                });

                return data;
            }
        },
        escapeMarkup: function (markup) { return markup; },
        templateResult: formatItem,
        templateSelection: formatItemSelection,
        placeholder : '{{ trans('global.search') }}...',
        language: {
            inputTooShort: function(args) {
                var remainingChars = args.minimum - args.input.length;

                return 'Escreva mais ' + remainingChars + (remainingChars === 1 ? ' carácter' : ' caracteres');
            },
            errorLoading: function() {
                return 'Não foi possível carregar os resultados';
            },
            searching: function() {
                return 'A pesquisar...';
            },
            noResults: function() {
                return 'Sem resultados para esta pesquisa';
            },
        }

    });
    $('.searchable-field').next('.select2').find('.select2-selection')
        .removeAttr('aria-labelledby')
        .attr('aria-label', 'Pesquisar viaturas e clientes');
    function formatItem (item) {
        if (item.loading) {
            return '{{ trans('global.searching') }}...';
        }
        var markup = "<a class='global-search-result-link' href='" + escapeGlobalSearchValue(item.url) + "'>";
        var icon = item.model === 'Viatura' ? 'fa-car' : 'fa-user';
        markup += "<div class='global-search-result-type'><i class='fa " + icon + "' aria-hidden='true'></i> " + escapeGlobalSearchValue(item.model) + "</div>";
        markup += "<div class='global-search-result-title'>" + escapeGlobalSearchValue(item.title) + "</div>";
        $.each(item.details || [], function(key, detail) {
            markup += "<div class='global-search-result-detail'><strong>" + escapeGlobalSearchValue(detail.label) + ":</strong> " + escapeGlobalSearchValue(detail.value) + "</div>";
        });
        markup += "</a>";

        return markup;
    }

    function formatItemSelection (item) {
        if (!item.model) {
            return '{{ trans('global.search') }}...';
        }
        return item.title || item.model;
    }
    function escapeGlobalSearchValue(value) {
        return $('<div>').text(value || '').html();
    }
    $('.searchable-field').on('change', function() {
        var selectedId = $(this).val();
        var selectedUrl = selectedId !== null ? globalSearchResultUrls[String(selectedId)] : null;

        if (selectedUrl) {
            window.location.assign(selectedUrl);
        }
    });
    $('.searchable-field').on('select2:selecting', function(event) {
        var selectedResult = event.params && event.params.args ? event.params.args.data : null;

        if (selectedResult && selectedResult.url) {
            window.location.assign(selectedResult.url);
        }
    });
    document.addEventListener('mousedown', function(event) {
        var resultLink = event.target.closest('.global-search-result-link');

        if (!resultLink) {
            return;
        }

        event.preventDefault();
        window.location.assign(resultLink.href);
    }, true);
});

    </script>
    @yield('scripts')
</body>

</html>
