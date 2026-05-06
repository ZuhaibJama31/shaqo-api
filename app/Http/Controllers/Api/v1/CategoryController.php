<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\v1\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    
    public function index()
    {
        return response()->json(Category::all());
    }

    public function show($id)
    {
        return response()->json(Category::find($id));

    }
} 
