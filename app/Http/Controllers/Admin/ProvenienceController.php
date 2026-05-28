<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provenience;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProvenienceController extends Controller
{
    public function store(Request $request)
    {
        abort_if(
            Gate::denies('client_create') && Gate::denies('client_edit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveniences', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $provenience = Provenience::create([
            'name' => $data['name'],
            'active' => true,
        ]);

        return response()->json([
            'id' => $provenience->id,
            'name' => $provenience->name,
        ], Response::HTTP_CREATED);
    }
}
