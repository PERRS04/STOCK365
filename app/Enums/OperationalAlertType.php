<?php

namespace App\Enums;

enum OperationalAlertType: string
{
    case ABANDONED_SESSION        = 'abandoned_session';
    case OPERATOR_ESCALATION      = 'operator_escalation';
    case PENDING_CLOSING          = 'pending_closing';
    case CRITICAL_STOCK           = 'critical_stock';
    case STALE_PENDING_MOVEMENT   = 'stale_pending_movement';
    case STALE_PENDING_COURTESY   = 'stale_pending_courtesy';

    public function severity(): OperationalStatus
    {
        return match($this) {
            self::ABANDONED_SESSION,
            self::OPERATOR_ESCALATION   => OperationalStatus::CRITICAL,

            self::PENDING_CLOSING,
            self::CRITICAL_STOCK,
            self::STALE_PENDING_MOVEMENT,
            self::STALE_PENDING_COURTESY => OperationalStatus::WARNING,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::ABANDONED_SESSION      => 'Sesión Abandonada',
            self::OPERATOR_ESCALATION    => 'Solicitud de Autorización',
            self::PENDING_CLOSING        => 'Cierre Pendiente',
            self::CRITICAL_STOCK         => 'Stock Crítico',
            self::STALE_PENDING_MOVEMENT => 'Depósito Sin Aprobar',
            self::STALE_PENDING_COURTESY => 'Cortesía Sin Aprobar',
        };
    }

    /**
     * SVG path `d` attribute for the semantic icon circle in the alert feed.
     */
    public function iconPath(): string
    {
        return match($this) {
            self::ABANDONED_SESSION =>
                'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            self::OPERATOR_ESCALATION =>
                'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            self::PENDING_CLOSING =>
                'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            self::CRITICAL_STOCK =>
                'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            self::STALE_PENDING_MOVEMENT =>
                'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            self::STALE_PENDING_COURTESY =>
                'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        };
    }
}
