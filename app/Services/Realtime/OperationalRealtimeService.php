<?php

namespace App\Services\Realtime;

use App\Enums\RealtimeEventType;
use App\Models\ActivityLog;
use App\Values\RealtimeEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Central realtime event normalizer.
 *
 * Responsibilities:
 * ─ Normalize activity_log rows into typed RealtimeEvent objects.
 * ─ Provide operator presence layer (online/idle classification).
 * ─ Cache aggressively to support multiple simultaneously-polling components.
 *
 * Cache strategy:
 * ─ Events:   8s TTL  — fresh enough for 12–15s pollers, cheap on DB.
 * ─ Presence: 20s TTL — operator status doesn't change sub-second.
 */
class OperationalRealtimeService
{
    private const EVENTS_TTL   = 8;
    private const PRESENCE_TTL = 20;
    private const EVENTS_KEY   = 'rt_events_v1';
    private const PRESENCE_KEY = 'rt_presence_v1';

    private const ONLINE_MINUTES = 5;
    private const IDLE_MINUTES   = 30;

    // ─── Events ─────────────────────────────────────────────────────────────

    public function getLatestEvents(int $limit = 20): Collection
    {
        return Cache::remember(self::EVENTS_KEY, self::EVENTS_TTL, function () use ($limit) {
            return ActivityLog::latest()
                ->limit($limit)
                ->get()
                ->map(fn (ActivityLog $log) => $this->normalize($log));
        });
    }

    public function flush(): void
    {
        Cache::forget(self::EVENTS_KEY);
        Cache::forget(self::PRESENCE_KEY);
    }

    // ─── Operator Presence ──────────────────────────────────────────────────

    /**
     * Returns a collection of active operators in the last 30 min.
     * Each item: ['user_id', 'name', 'sede_id', 'last_seen' (Carbon), 'status' (online|idle)]
     */
    public function getOperatorPresence(): Collection
    {
        return Cache::remember(self::PRESENCE_KEY, self::PRESENCE_TTL, function () {
            // Get latest activity timestamp per user in the last 30 min.
            $rows = DB::table('activity_logs')
                ->where('created_at', '>=', now()->subMinutes(self::IDLE_MINUTES))
                ->whereNotNull('user_id')
                ->selectRaw('user_id, user_name, sede_id, MAX(created_at) as last_seen')
                ->groupBy('user_id', 'user_name', 'sede_id')
                ->orderByDesc('last_seen')
                ->get();

            return $rows->map(function ($row) {
                $lastSeen = \Carbon\Carbon::parse($row->last_seen);
                $ageMin   = $lastSeen->diffInMinutes(now());

                return [
                    'user_id'   => $row->user_id,
                    'name'      => $row->user_name,
                    'sede_id'   => $row->sede_id,
                    'last_seen' => $lastSeen,
                    'status'    => $ageMin < self::ONLINE_MINUTES ? 'online' : 'idle',
                ];
            });
        });
    }

    /**
     * Presence grouped by sede_id.
     * Returns: [sede_id => Collection<presence>]
     */
    public function getPresenceBySede(): Collection
    {
        return $this->getOperatorPresence()->groupBy('sede_id');
    }

    // ─── Normalizer ─────────────────────────────────────────────────────────

    private function normalize(ActivityLog $log): RealtimeEvent
    {
        $type = RealtimeEventType::fromAction($log->action ?? '');

        return new RealtimeEvent(
            id:           $log->id,
            type:         $type,
            title:        $type->label(),
            description:  $log->description ?? $type->label(),
            sedeId:       $log->sede_id,
            sedeName:     $log->sede_id ? optional($log->relationLoaded('sede') ? $log->sede : null)?->nombre : null,
            operatorName: $log->user_name,
            timestamp:    $log->created_at,
            severity:     $type->severity(),
        );
    }
}
