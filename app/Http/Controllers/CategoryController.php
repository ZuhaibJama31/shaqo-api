<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminController;
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
