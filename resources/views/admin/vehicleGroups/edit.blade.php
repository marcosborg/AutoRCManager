@extends('layouts.admin')
@section('content')
@php($paymentsTabActive = old('return_to') === 'edit')
<div class="content">
    <div class="panel panel-default">
        <div class="panel-heading">
            Editar lote
            <a href="{{ route('admin.vehicle-groups.show', $vehicleGroup) }}" class="btn btn-xs btn-default pull-right">Visualizar lote</a>
        </div>
        <div class="panel-body">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="{{ $paymentsTabActive ? '' : 'active' }}">
                    <a href="#lot-data" aria-controls="lot-data" role="tab" data-toggle="tab">Dados do lote</a>
                </li>
                <li role="presentation" class="{{ $paymentsTabActive ? 'active' : '' }}">
                    <a href="#lot-payments" aria-controls="lot-payments" role="tab" data-toggle="tab">Liquida&ccedil;&atilde;o e pagamentos</a>
                </li>
            </ul>

            <div class="tab-content" style="padding-top: 15px;">
                <div role="tabpanel" class="tab-pane {{ $paymentsTabActive ? '' : 'active' }}" id="lot-data">
                    <form method="POST" action="{{ route('admin.vehicle-groups.update', [$vehicleGroup->id]) }}">
                        @method('PUT')
                        @csrf
                        @include('admin.vehicleGroups.partials.form', ['vehicleGroup' => $vehicleGroup])
                    </form>
                </div>
                <div role="tabpanel" class="tab-pane {{ $paymentsTabActive ? 'active' : '' }}" id="lot-payments">
                    @include('admin.vehicleGroups.partials.financial-summary')
                    @include('admin.vehicleGroups.partials.payment-management', ['paymentReturnTo' => 'edit'])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
@include('admin.vehicleGroups.partials.payment-scripts')
<script>
    $(function () {
        function activateLotTabFromHash() {
            if (window.location.hash === '#lot-payments') {
                $('a[href="#lot-payments"]').tab('show');
            }
        }

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            var target = $(event.target).attr('href');

            if (target === '#lot-data' || target === '#lot-payments') {
                window.history.replaceState(null, '', target);
            }
        });

        activateLotTabFromHash();
    });
</script>
@endsection
