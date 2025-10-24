<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles as Role;

class RoleController extends Controller
{
public function publicRoles()
{
    try {
        // Exclude Admin role (id=1)
        $roles = Role::where('id', '!=', 1)->get(['id', 'name']); // only id and name

        return response()->json([
            'status' => 200,
            'message' => 'Roles retrieved successfully',
            'data' => $roles
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
    }
}

    public function index()
    {
        try {
            $roles = Role::all();
            return response()->json([
                'status' => 200,
                'message' => 'Roles retrieved successfully',
                'data' => $roles
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'=>'required|string|max:255',
                'permissions'=>'nullable|string'
            ]);

            $role = Role::create($validated);
            return response()->json([
                'status'=>201,
                'message'=>'Role created successfully',
                'data'=>$role
            ],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            $validated = $request->validate([
                'name'=>'sometimes|required|string|max:255',
                'permissions'=>'nullable|string'
            ]);

            $role->update($validated);
            return response()->json([
                'status'=>200,
                'message'=>'Role updated successfully',
                'data'=>$role
            ],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();
            return response()->json([
                'status'=>200,
                'message'=>'Role deleted successfully'
            ],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
