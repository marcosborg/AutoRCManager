@php
    $selectedUsers = array_map('intval', $selectedUsers ?? []);
@endphp

<div class="form-group {{ $errors->has('users') ? 'has-error' : '' }}">
    <label for="users-filter">Utilizadores com este role</label>

    <div class="row" style="margin-bottom: 8px;">
        <div class="col-sm-6">
            <input type="text" class="form-control" id="users-filter" placeholder="Pesquisar utilizadores por nome ou email..." autocomplete="off">
        </div>
        <div class="col-sm-6 text-right" style="margin-top: 4px;">
            <button type="button" class="btn btn-info btn-xs" id="users-select-visible" style="border-radius: 0">{{ trans('global.select_all') }} (visiveis)</button>
            <button type="button" class="btn btn-info btn-xs" id="users-deselect-visible" style="border-radius: 0">{{ trans('global.deselect_all') }} (visiveis)</button>
            <span style="margin-left: 10px;">
                Selecionados: <strong id="users-selected-count">0</strong>
            </span>
        </div>
    </div>

    <div class="table-responsive" style="max-height: 320px; overflow-y: auto; border: 1px solid #ddd;">
        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 70px;">Usar</th>
                    <th>Nome</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody id="users-checklist">
                @foreach($users as $user)
                    @php
                        $checked = in_array((int) $user->id, $selectedUsers, true);
                        $search = strtolower(trim(($user->name ?? '') . ' ' . ($user->email ?? '')));
                    @endphp
                    <tr class="user-row" data-user-search="{{ e($search) }}">
                        <td class="text-center">
                            <input
                                type="checkbox"
                                class="user-cb"
                                name="users[]"
                                value="{{ $user->id }}"
                                {{ $checked ? 'checked' : '' }}
                            >
                        </td>
                        <td>{{ $user->name }}</td>
                        <td><code>{{ $user->email }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($errors->has('users'))
        <span class="help-block" role="alert">{{ $errors->first('users') }}</span>
    @endif
    <span class="help-block">Opcional: associe este role diretamente a utilizadores.</span>
</div>

