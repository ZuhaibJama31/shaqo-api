<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Anyone can view the list
    public function index()
    {
        return response()->json(Category::all());
    }

    // Anyone can view a single category
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }

    // ONLY ADMIN: Create
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admin can perform this action'], 403);
        }

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'name_so' => 'nullable|string|max:255',
            'icon'    => 'nullable|string|max:255',
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category 
        ], 201);
    }

    // ONLY ADMIN: Update
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admin can perform this action'], 403);
        }

        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'name_so' => 'nullable|string|max:255',
            'icon'    => 'sometimes|string|max:255',
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    // ONLY ADMIN: Delete
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only admin can perform this action'], 403);
        }

        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
