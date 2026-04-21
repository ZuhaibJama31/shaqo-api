<?php


namespace App\Http\Controllers\Admin;
use App\Models\Worker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WorkerController extends Controller
{
    /**
     * List available workers (filter by category_id and/or city)
     * GET /api/workers?category_id=1&city=Mogadishu
     */
    public function index(Request $request)
    {
        $query = Worker::with(['user', 'category'])
            ->where('is_available', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('city')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }

        $workers = $query->orderBy('rating', 'desc')->get();

        return response()->json($workers);
    }

    
    public function show($id)
    {
        $worker = Worker::with(['user', 'category'])->findOrFail($id);

        return response()->json($worker);
    }

    
    public function createOrUpdate(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can create a profile'], 403);
        }

        $data = $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'bio'              => 'nullable|string|max:500',
            'hourly_rate'      => 'required|numeric|min:0',
            'experience_years' => 'nullable|string|max:20',
            'is_available'     => 'boolean',
        ]);

        $worker = Worker::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'message' => 'Profile saved successfully',
            'worker'  => $worker->load(['user', 'category']),
        ]);
    }
}
