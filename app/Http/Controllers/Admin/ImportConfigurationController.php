<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOperationalAlertRecipientsRequest;
use App\Models\OperationalAlertRecipient;
use App\Models\User;
use Gate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ImportConfigurationController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('import_configuration_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::orderBy('name')->pluck('name', 'id');
        $tollsRecipient = OperationalAlertRecipient::firstOrCreate([
            'key' => OperationalAlertRecipient::KEY_TOLLS,
        ]);
        $tollsRecipient->load('users');
        $selectedTollsRecipientIds = $tollsRecipient->users->pluck('id');

        if ($selectedTollsRecipientIds->isEmpty() && $tollsRecipient->user_id) {
            $selectedTollsRecipientIds = collect([$tollsRecipient->user_id]);
        }

        return view('admin.importConfiguration.index', compact('selectedTollsRecipientIds', 'users'));
    }

    public function updateTollsRecipient(UpdateOperationalAlertRecipientsRequest $request)
    {
        $recipient = OperationalAlertRecipient::firstOrCreate([
            'key' => OperationalAlertRecipient::KEY_TOLLS,
        ]);
        $userIds = collect($request->validated('user_ids'))->map(fn ($userId) => (int) $userId)->unique()->values();

        DB::transaction(function () use ($recipient, $userIds): void {
            $recipient->users()->sync($userIds);
            $recipient->update(['user_id' => $userIds->first()]);
        });

        return redirect()->route('admin.import-configuration.index')->with('message', 'Responsáveis pelos alertas atualizados.');
    }
}
