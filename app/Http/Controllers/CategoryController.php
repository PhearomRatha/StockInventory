<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categories as Category;
use App\Helpers\ResponseHelper;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::all();
            return ResponseHelper::success('Categories retrieved successfully', $categories);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return ResponseHelper::success('Category retrieved successfully', $category);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
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
            return ResponseHelper::success('Category created successfully', $category, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
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
            return ResponseHelper::success('Category updated successfully', $category);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            return ResponseHelper::success('Category deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
