<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class IntegrationTokenController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => $request->user()->tokens()
            ->where('name', 'like', 'integration:%')
            ->orderByDesc('id')
            ->get(['id', 'name', 'created_at', 'last_used_at', 'expires_at'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'password' => ['required', 'string'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => ['Palavra-passe inválida.']]);
        }

        $expiry = now()->addDays($data['expires_in_days'] ?? 90);
        $token = $request->user()->createToken('integration:'.$data['name'], ['*'], $expiry);

        return response()->json([
            'id' => $token->accessToken->id,
            'token' => $token->plainTextToken,
            'expires_at' => $expiry->toIso8601String(),
        ], 201);
    }

    public function destroy(Request $request, int $token)
    {
        $stored = $request->user()->tokens()
            ->where('name', 'like', 'integration:%')
            ->findOrFail($token);
        $stored->delete();

        return response()->noContent();
    }
}
