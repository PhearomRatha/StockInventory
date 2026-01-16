<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categories as Category;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::all();
            return response()->json(['status'=>200,'message'=>'Categories retrieved successfully','data'=>$categories],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json(['status'=>200,'message'=>'Category retrieved successfully','data'=>$category],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'=>'required|string|max:255',
                'description'=>'nullable|string'
            ]);

            $category = Category::create($validated);
            return response()->json(['status'=>201,'message'=>'Category created successfully','data'=>$category],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $category = Category::findOrFail($id);
            $validated = $request->validate([
                'name'=>'sometimes|required|string|max:255',
                'description'=>'nullable|string'
            ]);

            $category->update($validated);
            return response()->json(['status'=>200,'message'=>'Category updated successfully','data'=>$category],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            return response()->json(['status'=>200,'message'=>'Category deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
