@if($vehicle->client)
    @can('client_edit')
        <p><a href="{{ route('admin.clients.edit', $vehicle->client) }}" target="_blank" rel="noopener noreferrer" data-document-buyer-link>Editar dados do cliente</a> <small>(abre noutro separador)</small></p>
        <p class="help-block">Depois de guardar a ficha do cliente, confira também os campos deste documento. As alterações não atualizam automaticamente o formulário já aberto.</p>
    @endcan
@endif
