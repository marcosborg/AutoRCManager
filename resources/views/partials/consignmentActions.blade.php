@can($viewGate)
    <a class="btn btn-xs btn-primary" href="{{ route('admin.' . $crudRoutePart . '.show', $row->id) }}">
        {{ trans('global.view') }}
    </a>
@endcan

@can($editGate)
    <a class="btn btn-xs btn-info" href="{{ route('admin.' . $crudRoutePart . '.edit', $row->id) }}">
        {{ trans('global.edit') }}
    </a>
@endcan

@can('vehicle_consignment_delete')
    <form action="{{ route('admin.' . $crudRoutePart . '.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Eliminar definitivamente esta consignação?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger">{{ trans('global.delete') }}</button>
    </form>
@endcan
