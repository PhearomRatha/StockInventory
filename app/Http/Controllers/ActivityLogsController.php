<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity_logs as ActivityLog;

class ActivityLogsController extends Controller
{
    // -----------------------------------------------------------
    // GET ALL LOGS
    // -----------------------------------------------------------
    public function index()
    {
        try {
            $logs = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user' => $log->user->name ?? 'Unknown',
                        'action' => $log->action,
                        'module' => $log->module,
                        'record_id' => $log->record_id,
                        'description' =>
                            ($log->user->name ?? 'User') .
                            " performed '{$log->action}' on {$log->module}" .
                            ($log->record_id ? " (Record ID: {$log->record_id})" : ""),
                        'created_at' => $log->created_at->format('Y-m-d H:i:s')
                    ];
                });

            return response()->json([
                'status' => 200,
                'message' => 'Activity logs retrieved successfully',
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------
    // SIMPLE FILTER (NEW)
    // -----------------------------------------------------------
    public function filter(Request $request)
    {
        try {
            $query = ActivityLog::with('user');

            // Filter by user ID
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by module
            if ($request->module) {
                $query->where('module', 'LIKE', "%{$request->module}%");
            }

            // Filter by action
            if ($request->action) {
                $query->where('action', 'LIKE', "%{$request->action}%");
            }

            // Filter by date range
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 200,
                'message' => 'Filtered logs retrieved',
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------
    // CREATE LOG
    // -----------------------------------------------------------
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'action' => 'required|string',
                'module' => 'required|string',
                'record_id' => 'nullable|integer'
            ]);

            $log = ActivityLog::create($validated);

            return response()->json([
                'status' => 201,
                'message' => 'Activity log created successfully',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------
    // UPDATE LOG
    // -----------------------------------------------------------
    public function update(Request $request, $id)
    {
        try {
            $log = ActivityLog::findOrFail($id);

            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'action' => 'sometimes|required|string',
                'module' => 'sometimes|required|string',
                'record_id' => 'nullable|integer'
            ]);

            $log->update($validated);

            return response()->json([
                'status' => 200,
                'message' => 'Activity log updated successfully',
                'data' => $log
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------
    // DELETE LOG
    // -----------------------------------------------------------
    public function destroy($id)
    {
        try {
            $log = ActivityLog::findOrFail($id);
            $log->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Activity log deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
