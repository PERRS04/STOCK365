<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        ActivityLog::create([
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_role'  => $user->getRoleNames()->first() ?? $user->role ?? 'unknown',
            'sede_id'    => $user->sede_id,
            'action'     => $action,
            'model_type' => $model ? class_basename($model) : null,
            'model_id'   => $model?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
