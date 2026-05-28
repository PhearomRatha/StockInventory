<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles as Role;
use App\Helpers\ResponseHelper;

class RoleController extends Controller
{
    /**
     * Get all roles
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);
        try {
            $perPage = $request->query('per_page', 15);
            
            // OPTIMIZED: Add pagination
            $roles = Role::select('id', 'name', 'description')
                ->paginate(min($perPage, 100));
            
            return ResponseHelper::success('Roles retrieved successfully', $roles);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Create a new role
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles',
                'description' => 'nullable|string'
            ]);

            $role = Role::create($validated);
            return ResponseHelper::success('Role created successfully', $role, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update a role
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $this->authorize('update', $role);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
                'description' => 'nullable|string'
            ]);

            $role->update($validated);
            return ResponseHelper::success('Role updated successfully', $role);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete a role
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();
            return ResponseHelper::success('Role deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * FIXED: changed to select('id', 'name')->get() to avoid exposing permissions JSON
     * Get public roles list
     */
    public function publicRoles()
    {
        try {
            $roles = Role::select('id', 'name')->get();
            return ResponseHelper::success('Public roles retrieved successfully', $roles);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
