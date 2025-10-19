<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity_logs as ActivityLog;

class ActivityLogsController extends Controller
{
    public function index()
    {
        try {
            $logs = ActivityLog::all();
            return response()->json(['status'=>200,'message'=>'Activity logs retrieved successfully','data'=>$logs],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id'=>'required|exists:users,id',
                'action'=>'required|string',
                'module'=>'required|string',
                'record_id'=>'nullable|integer'
            ]);

            $log = ActivityLog::create($validated);
            return response()->json(['status'=>201,'message'=>'Activity log created successfully','data'=>$log],201);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function update(Request $request,$id)
    {
        try {
            $log = ActivityLog::findOrFail($id);
            $validated = $request->validate([
                'user_id'=>'sometimes|required|exists:users,id',
                'action'=>'sometimes|required|string',
                'module'=>'sometimes|required|string',
                'record_id'=>'nullable|integer'
            ]);

            $log->update($validated);
            return response()->json(['status'=>200,'message'=>'Activity log updated successfully','data'=>$log],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        try {
            $log = ActivityLog::findOrFail($id);
            $log->delete();
            return response()->json(['status'=>200,'message'=>'Activity log deleted successfully'],200);
        } catch (\Exception $e) {
            return response()->json(['status'=>500,'message'=>$e->getMessage()],500);
        }
    }
}
