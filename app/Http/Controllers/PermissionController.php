<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permissions = \App\Models\Permission::all();

            return ResponseHelper::success('Permissions retrieved successfully', $permissions);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $permission = \App\Models\Permission::findOrFail($id);

            return ResponseHelper::success('Permission retrieved successfully', $permission);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions',
                'description' => 'nullable|string',
            ]);

            $permission = \App\Models\Permission::create($validated);

            return ResponseHelper::success('Permission created successfully', $permission, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $permission = \App\Models\Permission::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:permissions,name,' . $id,
                'description' => 'nullable|string',
            ]);

            $permission->update($validated);

            return ResponseHelper::success('Permission updated successfully', $permission);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $permission = \App\Models\Permission::findOrFail($id);
            $permission->delete();

            return ResponseHelper::success('Permission deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
