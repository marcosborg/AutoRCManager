@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.create') }} {{ trans('cruds.supplierOrder.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.supplier-orders.store") }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group {{ $errors->has('suplier') ? 'has-error' : '' }}">
                            <label class="required" for="suplier_id">{{ trans('cruds.supplierOrder.fields.suplier') }}</label>
                            <select class="form-control select2" name="suplier_id" id="suplier_id" required>
                                @foreach($supliers as $id => $entry)
                                    <option value="{{ $id }}" {{ old('suplier_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('suplier'))
                                <span class="help-block" role="alert">{{ $errors->first('suplier') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('repair') ? 'has-error' : '' }}">
                            <label for="repair_id">{{ trans('cruds.supplierOrder.fields.repair') }}</label>
                            <select class="form-control select2" name="repair_id" id="repair_id">
                                @foreach($repairs as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('repair_id', $selectedRepairId) == $id) ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('repair'))
                                <span class="help-block" role="alert">{{ $errors->first('repair') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('order_date') ? 'has-error' : '' }}">
                            <label class="required" for="order_date">{{ trans('cruds.supplierOrder.fields.order_date') }}</label>
                            <input class="form-control date" type="text" name="order_date" id="order_date" value="{{ old('order_date') }}" required>
                            @if($errors->has('order_date'))
                                <span class="help-block" role="alert">{{ $errors->first('order_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <label for="notes">{{ trans('cruds.supplierOrder.fields.notes') }}</label>
                            <textarea class="form-control" name="notes" id="notes">{{ old('notes') }}</textarea>
                            @if($errors->has('notes'))
                                <span class="help-block" role="alert">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
