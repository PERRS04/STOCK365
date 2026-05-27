<?php

namespace App\Services;

use App\Enums\OperationalIssueType;
use App\Enums\OperationalStatus;
use App\Models\CashSession;
use App\Models\User;
use App\Values\OperationalIssue;

/**
 * Evaluates the operational integrity state for a given operator.
 *
 * Rules (checked in priority order):
 *  1. pending_closing  → WARNING  (session awaiting supervisor approval)
 *  2. abandoned_open   → CRITICAL (session open across day boundary or > threshold)
 *
 * Returns null (HEALTHY) when no blocking issues are detected.
 * This service is stateless — safe to call multiple times per request.
 */
class OperationalIntegrityService
{
    /**
     * Sessions open longer than this many hours are considered abandoned.
     * Covers a full business day plus buffer.
     */
    private const ABANDON_THRESHOLD_HOURS = 16;

    public function evaluate(User $user): ?OperationalIssue
    {
        if ($user->sede_id === null) {
            return null;
        }

        // Query both 'open' and 'pending_closing' — note that activeForUser()
        // only returns 'open', so we query directly here.
        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $user->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return null; // No active session — EnsureCashSessionOpen handles that flow
        }

        // Priority 1: Operator submitted closing, waiting for approval
        if ($session->isPendingClosing()) {
            return new OperationalIssue(
                type:    OperationalIssueType::PENDING_CLOSING_APPROVAL,
                status:  OperationalStatus::WARNING,
                session: $session,
            );
        }

        // Priority 2: Session is open but spans a previous calendar day
        if ($session->isOpen() && !$session->opened_at->isToday()) {
            return new OperationalIssue(
                type:    OperationalIssueType::ABANDONED_SESSION,
                status:  OperationalStatus::CRITICAL,
                session: $session,
            );
        }

        // Priority 3: Session is open but exceeds intraday threshold
        if ($session->isOpen() && $session->opened_at->diffInHours(now()) >= self::ABANDON_THRESHOLD_HOURS) {
            return new OperationalIssue(
                type:    OperationalIssueType::ABANDONED_SESSION,
                status:  OperationalStatus::CRITICAL,
                session: $session,
            );
        }

        return null; // HEALTHY
    }
}
