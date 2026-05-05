<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Api\v1\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminClientController extends Controller
{
    // GET /admin/clients
    public function index()
    {
        return response()->json(
            User::where('role', 'client')
                ->latest()
                ->get()
        );
    }

    // POST /admin/clients
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|unique:users,phone',
            'city'     => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        $client = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'city'     => $data['city'] ?? null,
            'password' => Hash::make($data['password'] ?? '123456'),
            'role'     => 'client',
        ]);

        return response()->json($client, 201);
    }

    // GET /admin/clients/{id}
    public function show(string $id)
    {
        return response()->json(
            User::where('role', 'client')->findOrFail($id)
        );
    }

    // PUT /admin/clients/{id}
    public function update(Request $request, string $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:users,phone,' . $client->id,
            'city'  => 'sometimes|string|max:255',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    // DELETE /admin/clients/{id}
    public function destroy(string $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully'
        ]);
    }
}