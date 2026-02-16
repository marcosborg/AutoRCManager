@php
    use Illuminate\Support\Str;

    // $permissions: [id => title]
    // $selectedPermissions: [id, ...]
    $selectedPermissions = array_map('intval', $selectedPermissions ?? []);

    $knownActionOrder = ['access', 'create', 'edit', 'show', 'delete'];
    $actionLabels = [
        'access' => 'Acesso',
        'create' => 'Criar',
        'edit' => 'Editar',
        'show' => 'Ver',
        'delete' => 'Apagar',
    ];

    $matrix = [];
    $actionsFound = [];
    $singleTokenPermissions = [];

    foreach ($permissions as $id => $title) {
        $title = (string) $title;
        $parts = explode('_', $title);

        if (count($parts) <= 1) {
            $singleTokenPermissions[] = ['id' => (int) $id, 'title' => $title];
            continue;
        }

        $action = strtolower((string) array_pop($parts));
        $resourceKey = implode('_', $parts);

        $actionsFound[$action] = true;
        $matrix[$resourceKey][$action] = ['id' => (int) $id, 'title' => $title];
    }

    $dynamicActions = array_keys($actionsFound);
    usort($dynamicActions, static function (string $left, string $right) use ($knownActionOrder): int {
        $leftIndex = array_search($left, $knownActionOrder, true);
        $rightIndex = array_search($right, $knownActionOrder, true);
        $leftRank = $leftIndex === false ? 999 : $leftIndex;
        $rightRank = $rightIndex === false ? 999 : $rightIndex;

        if ($leftRank === $rightRank) {
            return strcmp($left, $right);
        }

        return $leftRank <=> $rightRank;
    });

    ksort($matrix);
@endphp

<div class="form-group {{ $errors->has('permissions') ? 'has-error' : '' }}">
    <label class="required">{{ trans('cruds.role.fields.permissions') }}</label>

    <div class="row" style="margin-bottom: 8px;">
        <div class="col-sm-6">
            <input type="text" class="form-control" id="permissions-filter" placeholder="Pesquisar permissoes (ex: vehicle, finance, access)..." autocomplete="off">
        </div>
        <div class="col-sm-6 text-right" style="margin-top: 4px;">
            <button type="button" class="btn btn-info btn-xs" id="permissions-select-visible" style="border-radius: 0">{{ trans('global.select_all') }} (visiveis)</button>
            <button type="button" class="btn btn-info btn-xs" id="permissions-deselect-visible" style="border-radius: 0">{{ trans('global.deselect_all') }} (visiveis)</button>
            <span style="margin-left: 10px;">
                Selecionadas: <strong id="permissions-selected-count">0</strong>
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="permissions-matrix" style="margin-bottom: 6px;">
            <thead>
                <tr>
                    <th style="min-width: 260px;">Recurso</th>
                    @foreach($dynamicActions as $action)
                        @php
                            $label = $actionLabels[$action] ?? (string) Str::of($action)->replace('_', ' ')->title();
                        @endphp
                        <th class="text-center" style="min-width: 95px;">{{ $label }}</th>
                    @endforeach
                    @if(!empty($singleTokenPermissions))
                        <th style="min-width: 220px;">Avulso</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $resourceKey => $actions)
                    @php
                        $resourceLabel = (string) Str::of($resourceKey)->replace('_', ' ')->replace('-', ' ')->title();
                        $rowSearch = strtolower($resourceKey . ' ' . $resourceLabel . ' ' . implode(' ', array_keys($actions)));
                    @endphp

                    <tr class="perm-row" data-perm-search="{{ e($rowSearch) }}" data-resource="{{ e($resourceKey) }}">
                        <td>
                            <div class="clearfix">
                                <div style="float:left;">
                                    <strong>{{ $resourceLabel }}</strong><br>
                                    <code>{{ $resourceKey }}</code>
                                </div>
                                <div style="float:right; margin-top: 2px;">
                                    <button type="button" class="btn btn-default btn-xs perm-row-all" style="border-radius: 0">Todos</button>
                                    <button type="button" class="btn btn-default btn-xs perm-row-none" style="border-radius: 0">Nenhum</button>
                                </div>
                            </div>
                        </td>

                        @foreach($dynamicActions as $action)
                            <td class="text-center">
                                @if(isset($actions[$action]))
                                    @php
                                        $perm = $actions[$action];
                                        $checked = in_array($perm['id'], $selectedPermissions, true);
                                        $inputId = 'perm_' . $resourceKey . '_' . $action;
                                    @endphp
                                    <div class="checkbox" style="margin: 0;">
                                        <label for="{{ $inputId }}" style="font-weight: 400;">
                                            <input
                                                id="{{ $inputId }}"
                                                type="checkbox"
                                                class="perm-cb"
                                                data-resource="{{ e($resourceKey) }}"
                                                data-action="{{ e($action) }}"
                                                name="permissions[]"
                                                value="{{ $perm['id'] }}"
                                                {{ $checked ? 'checked' : '' }}
                                            >
                                        </label>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach

                        @if(!empty($singleTokenPermissions))
                            <td class="text-muted">-</td>
                        @endif
                    </tr>
                @endforeach

                @if(!empty($singleTokenPermissions))
                    <tr class="perm-row" data-perm-search="avulso single token">
                        <td>
                            <strong>Permissoes avulsas</strong>
                        </td>
                        @foreach($dynamicActions as $action)
                            <td class="text-center text-muted">-</td>
                        @endforeach
                        <td>
                            @foreach($singleTokenPermissions as $perm)
                                @php $checked = in_array($perm['id'], $selectedPermissions, true); @endphp
                                <div class="checkbox" style="margin: 0 0 6px 0;">
                                    <label style="font-weight: 400;">
                                        <input
                                            type="checkbox"
                                            class="perm-cb"
                                            data-resource="__single__"
                                            name="permissions[]"
                                            value="{{ $perm['id'] }}"
                                            {{ $checked ? 'checked' : '' }}
                                        >
                                        <code>{{ $perm['title'] }}</code>
                                    </label>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if($errors->has('permissions'))
        <span class="help-block" role="alert">{{ $errors->first('permissions') }}</span>
    @endif
    <span class="help-block">{{ trans('cruds.role.fields.permissions_helper') }}</span>
</div>

