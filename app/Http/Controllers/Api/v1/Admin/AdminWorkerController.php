<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Api\v1\Controller;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AdminWorkerController extends Controller
{
    /**
     * List all workers (filter by category_id and/or city)
     * GET /api/admin/workers?category_id=1&city=Mogadishu
     */
    public function index(Request $request)
    {
        $query = Worker::with(['user', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        } 

        if ($request->filled('city')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $workers = $query->orderBy('rating', 'desc')->get();

        return response()->json($workers);
    }

    /**
     * Show single worker
     * GET /api/admin/workers/{id}
     */
    public function show($id)
    {
        $worker = Worker::with(['user', 'category'])->findOrFail($id);

        return response()->json($worker);
    }

    /**
     * Create a new worker (admin creates user + worker profile)
     * POST /api/admin/workers
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // User fields
            'name'             => 'required|string',
            'phone'            => 'required|string|unique:users,phone',
            'password'         => 'required|string|min:6',
            'city'             => 'nullable|string',

            // Worker fields
            'category_id'      => 'required|exists:categories,id',
            'bio'              => 'nullable|string|max:500',
            'hourly_rate'      => 'nullable|numeric|min:0',
            'experience_years' => 'nullable|string|max:20',
            'is_available'     => 'boolean',

            // Profile picture
            'profile_picture'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Normalize phone
        $phone = $this->normalizePhone($data['phone']);

        // Create user
        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $phone,
            'password' => Hash::make($data['password']),
            'city'     => $data['city'] ?? null,
            'role'     => 'worker',
        ]);

        // Handle profile picture
        $picturePath = null;
        if ($request->hasFile('profile_picture')) {
            $picturePath = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        // Create worker profile
        $worker = Worker::create([
            'user_id'          => $user->id,
            'category_id'      => $data['category_id'],
            'bio'              => $data['bio'] ?? null,
            'hourly_rate'      => $data['hourly_rate'],
            'experience_years' => $data['experience_years'] ?? null,
            'is_available'     => $data['is_available'] ?? true,
            'profile_picture'  => $picturePath,
        ]);

        return response()->json([
            'message' => 'Worker created successfully',
            'worker'  => $worker->load(['user', 'category']),
            'profile_picture_url' => $picturePath
                ? asset('storage/' . $picturePath)
                : null,
        ], 201);
    }

    /**
     * Update worker info + profile picture
     * POST /api/admin/workers/{id} (use POST for file upload, not PUT)
     */
    public function update(Request $request, $id)
    {
        $worker = Worker::with('user')->findOrFail($id);

        $data = $request->validate([
            // User fields
            'name'             => 'nullable|string',
            'city'             => 'nullable|string',
            'phone'            => 'nullable|string|unique:users,phone,' . $worker->user_id,
            'password'         => 'nullable|string|min:6',

            // Worker fields
            'category_id'      => 'nullable|exists:categories,id',
            'bio'              => 'nullable|string|max:500',
            'hourly_rate'      => 'nullable|numeric|min:0',
            'experience_years' => 'nullable|string|max:20',
            'is_available'     => 'boolean',

            // Profile picture
            'profile_picture'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Update user fields
        $userFields = array_filter([
            'name'  => $data['name'] ?? null,
            'city'  => $data['city'] ?? null,
            'phone' => isset($data['phone'])
                ? $this->normalizePhone($data['phone'])
                : null,
            'password' => isset($data['password'])
                ? Hash::make($data['password'])
                : null,
        ]);

        if (!empty($userFields)) {
            $worker->user->update($userFields);
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old picture
            if ($worker->profile_picture) {
                $oldPath = public_path('storage/' . $worker->profile_picture);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $data['profile_picture'] = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        // Update worker fields
        $workerFields = array_filter([
            'category_id'      => $data['category_id'] ?? null,
            'bio'              => $data['bio'] ?? null,
            'hourly_rate'      => $data['hourly_rate'] ?? null,
            'experience_years' => $data['experience_years'] ?? null,
            'is_available'     => $data['is_available'] ?? null,
            'profile_picture'  => $data['profile_picture'] ?? null,
        ]);

        $worker->update($workerFields);

        return response()->json([
            'message' => 'Worker updated successfully',
            'worker'  => $worker->fresh()->load(['user', 'category']),
            'profile_picture_url' => $worker->profile_picture
                ? asset('storage/' . $worker->profile_picture)
                : null,
        ]);
    }

    /**
     * Delete worker + user account
     * DELETE /api/admin/workers/{id}
     */
    public function destroy($id)
    {
        $worker = Worker::with('user')->findOrFail($id);

        // Delete profile picture file
        if ($worker->profile_picture) {
            $path = public_path('storage/' . $worker->profile_picture);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $worker->user->delete(); // cascades to worker if set in DB
        // or delete separately: $worker->delete();

        return response()->json([
            'message' => 'Worker deleted successfully'
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function normalizePhone($phone)
    {
        $phone = trim($phone);

        if (preg_match('/^0/', $phone)) {
            return '+252' . substr($phone, 1);
        }

        return $phone;
    }
}