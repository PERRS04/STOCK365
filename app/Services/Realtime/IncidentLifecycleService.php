<?php

namespace App\Services\Realtime;

use App\Enums\IncidentStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed incident state machine — no migration required.
 * State is ephemeral (4h TTL); persistent record lives in activity_logs.
 *
 * Cache key: incident_state_{alertId}
 * Stored value: ['status', 'by_name', 'at', 'note']
 */
class IncidentLifecycleService
{
    private const TTL_SECONDS = 14400; // 4 hours

    private function key(string $alertId): string
    {
        return "incident_state_{$alertId}";
    }

    public function getStatus(string $alertId): IncidentStatus
    {
        $state = Cache::get($this->key($alertId));
        if (!$state) return IncidentStatus::OPEN;

        return IncidentStatus::tryFrom($state['status']) ?? IncidentStatus::OPEN;
    }

    public function getState(string $alertId): array
    {
        return Cache::get($this->key($alertId), [
            'status'  => IncidentStatus::OPEN->value,
            'by_name' => null,
            'at'      => null,
            'note'    => null,
        ]);
    }

    public function acknowledge(string $alertId, User $by): void
    {
        $this->transition($alertId, IncidentStatus::ACKNOWLEDGED, $by, '');
    }

    public function markInProgress(string $alertId, User $by): void
    {
        $this->transition($alertId, IncidentStatus::IN_PROGRESS, $by, '');
    }

    public function resolve(string $alertId, User $by, string $note = ''): void
    {
        $this->transition($alertId, IncidentStatus::RESOLVED, $by, $note);
    }

    public function ignore(string $alertId, User $by): void
    {
        $this->transition($alertId, IncidentStatus::IGNORED, $by, '');
    }

    public function reset(string $alertId): void
    {
        Cache::forget($this->key($alertId));
    }

    private function transition(string $alertId, IncidentStatus $to, User $by, string $note): void
    {
        Cache::put($this->key($alertId), [
            'status'  => $to->value,
            'by_name' => $by->name,
            'at'      => now()->toIso8601String(),
            'note'    => $note,
        ], self::TTL_SECONDS);

        ActivityLogger::log(
            'incident.' . $to->value,
            "Incidente {$alertId} → {$to->label()} por {$by->name}" . ($note ? ": {$note}" : ''),
            null,
            ['status' => IncidentStatus::OPEN->value],
            ['status' => $to->value, 'alert_id' => $alertId],
        );
    }
}
