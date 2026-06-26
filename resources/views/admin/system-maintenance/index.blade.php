@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Manutencao do sistema</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Comandos</h4>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-right:8px;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="config-clear">
                                <button type="submit" class="btn btn-warning">php artisan config:clear</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-right:8px;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="cache-clear">
                                <button type="submit" class="btn btn-warning">php artisan cache:clear</button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-maintenance.run') }}" style="display:inline-block;margin-bottom:8px;">
                                @csrf
                                <input type="hidden" name="action" value="clear-all">
                                <button type="submit" class="btn btn-danger">Limpar configuracao e cache</button>
                            </form>

                            @if(session('command_output'))
                                <pre style="margin-top:15px;white-space:pre-wrap;">{{ session('command_output') }}</pre>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h4>Configuracao carregada</h4>
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    @foreach($leadConfig as $key => $value)
                                        <tr>
                                            <th style="width:45%;">{{ $key }}</th>
                                            <td>{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach($mailConfig as $key => $value)
                                        <tr>
                                            <th style="width:45%;">{{ $key }}</th>
                                            <td>{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
