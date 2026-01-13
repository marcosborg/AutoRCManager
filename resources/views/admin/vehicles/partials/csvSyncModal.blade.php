<div class="modal fade" id="vehicleCsvSyncModal" tabindex="-1" role="dialog" aria-labelledby="vehicleCsvSyncModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="vehicleCsvSyncModalLabel">Sync CSV de viaturas</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <form class="form-horizontal" method="POST" action="{{ route('admin.vehicles.syncCsvParse') }}" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="form-group{{ $errors->has('csv_file') ? ' has-error' : '' }}">
                                <label for="csv_file" class="col-md-4 control-label">CSV</label>
                                <div class="col-md-6">
                                    <input id="csv_file" type="file" class="form-control" name="csv_file" required>
                                    @if($errors->has('csv_file'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('csv_file') }}</strong>
                                        </span>
                                    @endif
                                    <span class="help-block">Vai escolher as colunas no passo seguinte.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <div class="checkbox">
                                        <input type="hidden" name="has_header" value="0">
                                        <label>
                                            <input type="checkbox" name="has_header" value="1" checked> CSV tem cabecalho
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('general_state_id') ? ' has-error' : '' }}">
                                <label for="general_state_id" class="col-md-4 control-label">General state (default)</label>
                                <div class="col-md-6">
                                    <select id="general_state_id" class="form-control" name="general_state_id">
                                        <option value="">Auto (primeiro)</option>
                                        @foreach($general_states as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('general_state_id'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('general_state_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group{{ $errors->has('delimiter') ? ' has-error' : '' }}">
                                <label for="delimiter" class="col-md-4 control-label">Delimiter</label>
                                <div class="col-md-6">
                                    <input id="delimiter" type="text" class="form-control" name="delimiter" placeholder="Auto">
                                    @if($errors->has('delimiter'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('delimiter') }}</strong>
                                        </span>
                                    @endif
                                    <span class="help-block">Ex: ; , ou \t</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-8 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary">
                                        Continuar
                                    </button>
                                </div>
                            </div>
                        </form>
                        <p class="text-muted">A sync remove viaturas fora do CSV e cria apenas as que faltam.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
