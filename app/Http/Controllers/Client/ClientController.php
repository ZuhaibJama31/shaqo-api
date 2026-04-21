<?php 

namespace App\Http\Controllers\Client;
use App\Http\Controllers\Controller;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'  => 'required|exists:users,id',
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|unique:clients,phone',
            'city'     => 'required|string|max:255',
            'address'  => 'nullable|string',
        ]);

        $client = Client::create($data);

        return response()->json($client, 201);
    }

    public function show(string $id)
    {
        return response()->json(Client::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'sometimes|string|unique:clients,phone,' . $client->id,
            'city'    => 'sometimes|string|max:255',
            'address' => 'nullable|string',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(string $id)
    {
        Client::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Client deleted successfully'
        ]);
    }
}