<?php

namespace App\Enums;

enum RealtimeEventType: string
{
    case SALE_CREATED          = 'sale_created';
    case CASH_SESSION_OPENED   = 'cash_session_opened';
    case CASH_SESSION_CLOSED   = 'cash_session_closed';
    case FORCE_CLOSE           = 'force_close';
    case DEPOSIT_RECEIVED      = 'deposit_received';
    case WITHDRAWAL_MADE       = 'withdrawal_made';
    case APPROVAL_GRANTED      = 'approval_granted';
    case APPROVAL_REJECTED     = 'approval_rejected';
    case STOCK_ALERT           = 'stock_alert';
    case OPERATOR_ESCALATION   = 'operator_escalation';
    case COURTESY_APPROVED     = 'courtesy_approved';
    case INVENTORY_RECEIVED    = 'inventory_received';
    case GENERIC               = 'generic';

    public static function fromAction(string $action): self
    {
        $a = strtolower($action);

        return match(true) {
            str_contains($a, 'force_close')                                    => self::FORCE_CLOSE,
            str_contains($a, 'cash_session') && str_contains($a, 'close')     => self::CASH_SESSION_CLOSED,
            str_contains($a, 'cash_session') && str_contains($a, 'open')      => self::CASH_SESSION_OPENED,
            str_contains($a, 'escalation')                                     => self::OPERATOR_ESCALATION,
            str_contains($a, 'venta') || str_contains($a, 'sale')             => self::SALE_CREATED,
            str_contains($a, 'movimiento') || str_contains($a, 'deposito')    => self::DEPOSIT_RECEIVED,
            str_contains($a, 'egreso') || str_contains($a, 'retiro')          => self::WITHDRAWAL_MADE,
            str_contains($a, 'aprobacion') || str_contains($a, 'approve')     => self::APPROVAL_GRANTED,
            str_contains($a, 'rechazo') || str_contains($a, 'reject')         => self::APPROVAL_REJECTED,
            str_contains($a, 'alerta') || str_contains($a, 'alert')           => self::STOCK_ALERT,
            str_contains($a, 'cortesia') || str_contains($a, 'courtesy')      => self::COURTESY_APPROVED,
            str_contains($a, 'recepcion') || str_contains($a, 'receipt')      => self::INVENTORY_RECEIVED,
            default                                                             => self::GENERIC,
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::FORCE_CLOSE, self::STOCK_ALERT, self::OPERATOR_ESCALATION  => 'critical',
            self::APPROVAL_REJECTED, self::WITHDRAWAL_MADE                   => 'warning',
            self::CASH_SESSION_CLOSED, self::APPROVAL_GRANTED,
            self::COURTESY_APPROVED, self::INVENTORY_RECEIVED                => 'info',
            default                                                           => 'low',
        };
    }

    public function label(): string
    {
        return match($this) {
            self::SALE_CREATED        => 'Venta',
            self::CASH_SESSION_OPENED => 'Caja abierta',
            self::CASH_SESSION_CLOSED => 'Caja cerrada',
            self::FORCE_CLOSE         => 'Cierre forzado',
            self::DEPOSIT_RECEIVED    => 'Depósito',
            self::WITHDRAWAL_MADE     => 'Egreso',
            self::APPROVAL_GRANTED    => 'Aprobación',
            self::APPROVAL_REJECTED   => 'Rechazo',
            self::STOCK_ALERT         => 'Alerta stock',
            self::OPERATOR_ESCALATION => 'Escalación',
            self::COURTESY_APPROVED   => 'Cortesía',
            self::INVENTORY_RECEIVED  => 'Recepción',
            self::GENERIC             => 'Evento',
        };
    }

    /** SVG path d attribute for the semantic icon circle. */
    public function iconPath(): string
    {
        return match($this) {
            self::SALE_CREATED =>
                'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            self::CASH_SESSION_OPENED =>
                'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            self::CASH_SESSION_CLOSED =>
                'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            self::FORCE_CLOSE =>
                'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
            self::DEPOSIT_RECEIVED =>
                'M12 4v16m8-8H4',
            self::WITHDRAWAL_MADE =>
                'M20 12H4',
            self::APPROVAL_GRANTED =>
                'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            self::APPROVAL_REJECTED =>
                'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            self::STOCK_ALERT =>
                'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            self::OPERATOR_ESCALATION =>
                'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            self::COURTESY_APPROVED =>
                'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            self::INVENTORY_RECEIVED =>
                'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            self::GENERIC =>
                'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    public function iconBg(): string
    {
        return match($this->severity()) {
            'critical' => 'bg-red-50',
            'warning'  => 'bg-amber-50',
            'info'     => 'bg-emerald-50',
            default    => 'bg-gray-100',
        };
    }

    public function iconClr(): string
    {
        return match($this) {
            self::SALE_CREATED        => 'text-emerald-600',
            self::CASH_SESSION_OPENED => 'text-blue-600',
            self::CASH_SESSION_CLOSED => 'text-teal-600',
            self::FORCE_CLOSE         => 'text-red-500',
            self::DEPOSIT_RECEIVED    => 'text-teal-600',
            self::WITHDRAWAL_MADE     => 'text-amber-600',
            self::APPROVAL_GRANTED    => 'text-emerald-600',
            self::APPROVAL_REJECTED   => 'text-red-500',
            self::STOCK_ALERT         => 'text-red-500',
            self::OPERATOR_ESCALATION => 'text-red-500',
            self::COURTESY_APPROVED   => 'text-pink-500',
            self::INVENTORY_RECEIVED  => 'text-indigo-600',
            self::GENERIC             => 'text-gray-400',
        };
    }

    public function priorityBarClr(): string
    {
        return match($this->severity()) {
            'critical' => 'bg-red-500',
            'warning'  => 'bg-amber-400',
            'info'     => 'bg-transparent',
            default    => 'bg-transparent',
        };
    }
}
