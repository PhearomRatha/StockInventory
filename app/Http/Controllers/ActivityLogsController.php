<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity_logs as ActivityLog;
use App\Helpers\ResponseHelper;

class ActivityLogsController extends Controller
{
    /**
     * Get all activity logs
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 15;
            // OPTIMIZED: Ensure proper pagination with limit
            $logs = ActivityLog::with('user')
                ->latest()
                ->paginate(min($perPage, 100)); // Cap at 100 records
            
            return ResponseHelper::success('Activity logs retrieved successfully', $logs);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Filter activity logs
     */
    public function filter(Request $request)
    {
        try {
            $query = ActivityLog::query();

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            // OPTIMIZED: Use count() directly and add limit
            $totalCount = $query->count();
            
            $logs = $query->with(['user'])
                ->latest()
                ->limit(100) // Cap at 100 records
                ->get();
            
            return ResponseHelper::success('Activity logs filtered successfully', [
                'total' => $totalCount,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Create an activity log
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string',
                'description' => 'required|string'
            ]);

            $log = ActivityLog::create($validated);
            return ResponseHelper::success('Activity log created successfully', $log, 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update an activity log
     */
    public function update(Request $request, $id)
    {
        try {
            $log = ActivityLog::findOrFail($id);

            $validated = $request->validate([
                'type' => 'sometimes|required|string',
                'description' => 'sometimes|required|string'
            ]);

            $log->update($validated);
            return ResponseHelper::success('Activity log updated successfully', $log);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete an activity log
     */
    public function destroy($id)
    {
        try {
            $log = ActivityLog::findOrFail($id);
            $log->delete();
            return ResponseHelper::success('Activity log deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
