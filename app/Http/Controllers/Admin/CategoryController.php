<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    
    public function index(Request $request)
    {
        
            return response()->json(Category::all());
        
    }

    public function show($id)
    {
        return response()->json(Category::find($id));

    }

    public function store(Request $request){
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Only admin can create categories'], 403);
        }

         $data = $request->validate([
            'name'    => 'required|string|max:255',
            'name_so'  => 'required|string|max:255',
            'icon'      => 'required|string|max:255',
            
        ]);

        $category = Category::create([
            'name'    => $data['name'],
            'name_so'  => $data['name_so'],
            'icon'      => $data['icon'],
        ]);

        return response()->json([
            'message' => 'Categories request sent successfully',
            $category 
            
        ], 201);
    }
}
