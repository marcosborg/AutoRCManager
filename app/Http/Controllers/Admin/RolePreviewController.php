<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePreviewController extends Controller
{
    public function store(Request $request)
    {
        abort_if(! RolePreview::isRealAdmin($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($data['role_id']);
        session(['role_preview.role_id' => $role->id]);

        return back()->with('message', 'Role temporario ativo: ' . $role->title);
    }

    public function destroy(Request $request)
    {
        abort_if(! RolePreview::isRealAdmin($request->user()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        session()->forget('role_preview.role_id');

        return back()->with('message', 'Role temporario removido.');
    }
}
