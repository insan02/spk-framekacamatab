<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log($action, $module, $referenceId, $oldValues = null, $newValues = null, $description = '')
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        
        ActivityLog::create([
            'user_id' => $user->user_id,
            'user_name' => $user->name,
            'action' => $action,
            'module' => $module,
            'reference_id' => $referenceId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'description' => $description
        ]);
        
        return true;
    }
}