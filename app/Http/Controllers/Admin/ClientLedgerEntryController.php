<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyClientLedgerEntryRequest;
use App\Http\Requests\StoreClientLedgerEntryRequest;
use App\Http\Requests\UpdateClientLedgerEntryRequest;
use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\Vehicle;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ClientLedgerEntryController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('client_ledger_entry_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clientLedgerEntries = ClientLedgerEntry::with(['client', 'vehicle'])
            ->orderBy('entry_date', 'desc')
            ->get();

        return view('admin.clientLedgerEntries.index', compact('clientLedgerEntries'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('client_ledger_entry_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');
        $entryTypes = [
            'debit' => 'Debito',
            'credit' => 'Credito',
        ];
        $selectedClientId = $request->query('client_id');

        return view('admin.clientLedgerEntries.create', compact('clients', 'vehicles', 'entryTypes', 'selectedClientId'));
    }

    public function store(StoreClientLedgerEntryRequest $request)
    {
        $entry = ClientLedgerEntry::create($request->all());

        if ($request->hasFile('attachment')) {
            $entry->addMediaFromRequest('attachment')->toMediaCollection('attachment');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $entry->id]);
        }

        return redirect()->route('admin.client-ledger-entries.index');
    }

    public function edit(ClientLedgerEntry $clientLedgerEntry)
    {
        abort_if(Gate::denies('client_ledger_entry_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $vehicles = Vehicle::pluck('license', 'id')->prepend(trans('global.pleaseSelect'), '');
        $entryTypes = [
            'debit' => 'Debito',
            'credit' => 'Credito',
        ];

        $clientLedgerEntry->load(['client', 'vehicle']);

        return view('admin.clientLedgerEntries.edit', compact('clientLedgerEntry', 'clients', 'vehicles', 'entryTypes'));
    }

    public function update(UpdateClientLedgerEntryRequest $request, ClientLedgerEntry $clientLedgerEntry)
    {
        $clientLedgerEntry->update($request->all());

        if ($request->boolean('clear_attachment')) {
            $clientLedgerEntry->clearMediaCollection('attachment');
        }

        if ($request->hasFile('attachment')) {
            $clientLedgerEntry->clearMediaCollection('attachment');
            $clientLedgerEntry->addMediaFromRequest('attachment')->toMediaCollection('attachment');
        }

        return redirect()->route('admin.client-ledger-entries.index');
    }

    public function show(ClientLedgerEntry $clientLedgerEntry)
    {
        abort_if(Gate::denies('client_ledger_entry_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clientLedgerEntry->load(['client', 'vehicle']);

        return view('admin.clientLedgerEntries.show', compact('clientLedgerEntry'));
    }

    public function destroy(ClientLedgerEntry $clientLedgerEntry)
    {
        abort_if(Gate::denies('client_ledger_entry_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clientLedgerEntry->delete();

        return back();
    }

    public function massDestroy(MassDestroyClientLedgerEntryRequest $request)
    {
        $entries = ClientLedgerEntry::find(request('ids'));

        foreach ($entries as $entry) {
            $entry->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
