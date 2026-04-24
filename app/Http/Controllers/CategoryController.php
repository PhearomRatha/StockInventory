<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Categories as Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);

            // OPTIMIZED: Add pagination and select columns
            $categories = Category::select('id', 'name', 'description')
                ->paginate(min($perPage, 100));

            return ResponseHelper::success('Categories retrieved successfully', $categories);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get category by ID
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);

            return ResponseHelper::success('Category retrieved successfully', $category);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(StoreCategoryRequest $request)
    {
        try {
            $validated = $request->validated();

            $category = Category::create($validated);

            return ResponseHelper::success('Category created successfully', $category, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(StoreCategoryRequest $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $validated = $request->validated();

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
