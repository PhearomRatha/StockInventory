<?php

namespace App\Helpers;

use App\Models\Activity_logs as ActivityLog;

class ActivityLogHelper
{
    /**
     * Log an activity
     */
    public static function log($action, $module, $recordId = null, $userId = null)
    {
        return ActivityLog::create([
            'user_id' => $userId ?: auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId
        ]);
    }

    /**
     * Log creation
     */
    public static function logCreated($module, $recordId = null, $userId = null)
    {
        return self::log('created', $module, $recordId, $userId);
    }

    /**
     * Log update
     */
    public static function logUpdated($module, $recordId = null, $userId = null)
    {
        return self::log('updated', $module, $recordId, $userId);
    }

    /**
     * Log deletion
     */
    public static function logDeleted($module, $recordId = null, $userId = null)
    {
        return self::log('deleted', $module, $recordId, $userId);
    }
}