<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            $model->logActivity('created', $model->toArray());
        });

        static::updated(function (Model $model) {
            $model->logActivity('updated', $model->toArray());
        });
    }

    protected function logActivity(string $action, array $data): void
    {
        // Implementar logging de actividades si es necesario
        // Aquí se pueden guardar los cambios en una tabla de auditoría
    }
}
