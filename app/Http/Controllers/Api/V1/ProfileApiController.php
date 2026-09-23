<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileApiController extends Controller
{
    public function show(Request $request)
    {
        Gate::authorize('profile_password_edit');

        return response()->json(['data' => $request->user()->only('id', 'name', 'email', 'mobile_phone')]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return $this->show($request);
    }

    public function password(UpdatePasswordRequest $request)
    {
        $data = $request->validate(['current_password' => ['required', 'string']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => ['Palavra-passe atual inválida.']]);
        }

        $request->user()->update($request->validated());

        return response()->json(['message' => 'Palavra-passe atualizada.']);
    }

    public function destroy(Request $request)
    {
        Gate::authorize('profile_password_edit');
        $data = $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => ['Palavra-passe inválida.']]);
        }

        $user = $request->user();
        $user->update(['email' => time().'_'.$user->email]);
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
