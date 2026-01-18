<?php
namespace App\Http\Controllers;

use App\Models\Activity_logs as ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Get all users with role
public function index()
{
    try {
        $users = User::select('id', 'name', 'email', 'status', 'role_id')
            ->with(['role:id,name'])
            ->paginate(8);

  

        return response()->json([
            'status'  => 200,
            'message' => 'Users retrieved successfully',
            'data'    => $users,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => $e->getMessage()
        ], 500);
    }
}


    // Create new user (pending by default)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['role_id']  = 2; // default role
            $validated['status']   = 0; // pending

            $user = User::create($validated);

            if (auth()->id()) {
                ActivityLog::create([
                    'user_id'   => auth()->id(),
                    'action'    => 'created',
                    'module'    => 'users',
                    'record_id' => $user->id,
                ]);
            }

            return response()->json([
                'status'  => 201,
                'message' => 'User created successfully',
                'data'    => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // Update user info or status
    public function update(Request $request, $id)
    {
        try {
            // Find user
            $user = User::with('role:id,name')->findOrFail($id);

            // Validate input
            $validated = $request->validate([
                'name'     => 'sometimes|string|max:255',
                'email'    => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|nullable|string|min:6',
                'status'   => 'sometimes|integer|in:0,1,2',
                'role_id'  => 'sometimes|nullable|exists:roles,id',
            ]);

            // Handle password
            if (isset($validated['password']) && !empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            // Update only provided fields
            $user->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'User updated successfully',
                'data'    => $user,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to update user',
                'error'   => $e->getMessage(), // <-- see the real error
            ], 500);
        }

    }

    // Soft delete / deactivate user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Soft delete: set status to inactive
            $user->update(['status' => 2]);

            if (auth()->id()) {
                ActivityLog::create([
                    'user_id'   => auth()->id(),
                    'action'    => 'deactivated',
                    'module'    => 'users',
                    'record_id' => $user->id,
                ]);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'User deactivated successfully',
                'data'    => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // Get total users by role (cached)
    public function totalUsers()
    {
        try {
            return Cache::remember('total_users_by_role', 10, function () {
                $usersByRole = User::join('roles', 'users.role_id', '=', 'roles.id')
                    ->select('roles.name as role', DB::raw('count(*) as count'))
                    ->groupBy('roles.name')
                    ->pluck('count', 'role');

                return response()->json([
                    'status'        => 200,
                    'total_admin'   => $usersByRole['admin'] ?? 0,
                    'total_manager' => $usersByRole['manager'] ?? 0,
                    'total_staff'   => $usersByRole['staff'] ?? 0,
                    'total_users'   => $usersByRole->sum(),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
