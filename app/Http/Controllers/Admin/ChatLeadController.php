<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatLeadRequest;
use App\Http\Requests\UpdateChatLeadRequest;
use App\Models\ChatChannel;
use App\Models\ChatLead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ChatLeadController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('chat_lead_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $chatLeads = ChatLead::with(['channel', 'assigned_user', 'meta_lead'])->latest()->paginate(50);

        return view('admin.chatLeads.index', compact('chatLeads'));
    }

    public function create()
    {
        abort_if(Gate::denies('chat_lead_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $chatLead = new ChatLead(['priority' => 'low', 'status' => 'open']);
        $channels = ChatChannel::pluck('name', 'id')->prepend('Sem canal', '');
        $users = User::pluck('name', 'id')->prepend('Sem vendedor', '');
        $priorities = ChatLead::PRIORITY_SELECT;
        $statuses = ChatLead::STATUS_SELECT;

        return view('admin.chatLeads.create', compact('chatLead', 'channels', 'users', 'priorities', 'statuses'));
    }

    public function store(StoreChatLeadRequest $request)
    {
        $data = $request->validated();
        $data['wants_financing'] = $request->boolean('wants_financing');
        $data['has_trade_in'] = $request->boolean('has_trade_in');
        ChatLead::create($data);

        return redirect()->route('admin.chat-leads.index');
    }

    public function show(ChatLead $chatLead)
    {
        abort_if(Gate::denies('chat_lead_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $chatLead->load(['channel', 'assigned_user', 'meta_lead', 'conversations.messages']);

        return view('admin.chatLeads.show', compact('chatLead'));
    }

    public function edit(ChatLead $chatLead)
    {
        abort_if(Gate::denies('chat_lead_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $channels = ChatChannel::pluck('name', 'id')->prepend('Sem canal', '');
        $users = User::pluck('name', 'id')->prepend('Sem vendedor', '');
        $priorities = ChatLead::PRIORITY_SELECT;
        $statuses = ChatLead::STATUS_SELECT;

        return view('admin.chatLeads.edit', compact('chatLead', 'channels', 'users', 'priorities', 'statuses'));
    }

    public function update(UpdateChatLeadRequest $request, ChatLead $chatLead)
    {
        $data = $request->validated();
        $data['wants_financing'] = $request->boolean('wants_financing');
        $data['has_trade_in'] = $request->boolean('has_trade_in');
        $chatLead->update($data);

        return redirect()->route('admin.chat-leads.index');
    }

    public function destroy(ChatLead $chatLead)
    {
        abort_if(Gate::denies('chat_lead_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $chatLead->delete();

        return back();
    }
}
