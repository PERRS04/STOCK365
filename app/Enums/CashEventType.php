<?php

namespace App\Enums;

enum CashEventType: string
{
    case SALE              = 'sale';
    case WITHDRAWAL        = 'withdrawal';
    case INFLOW            = 'inflow';
    case COURTESY          = 'courtesy';
    case SESSION_OPEN      = 'session_open';
    case PENDING_MOVEMENT  = 'pending_movement';
    case PENDING_COURTESY  = 'pending_courtesy';

    public function label(): string
    {
        return match($this) {
            self::SALE             => 'Venta',
            self::WITHDRAWAL       => 'Egreso',
            self::INFLOW           => 'Ingreso',
            self::COURTESY         => 'Cortesía',
            self::SESSION_OPEN     => 'Apertura',
            self::PENDING_MOVEMENT => 'Pendiente',
            self::PENDING_COURTESY => 'Cortesía pendiente',
        };
    }

    /** 3px left accent bar color class. */
    public function barClr(): string
    {
        return match($this) {
            self::SALE             => 'bg-emerald-400',
            self::WITHDRAWAL       => 'bg-amber-400',
            self::INFLOW           => 'bg-teal-400',
            self::COURTESY         => 'bg-pink-400',
            self::SESSION_OPEN     => 'bg-blue-400',
            self::PENDING_MOVEMENT,
            self::PENDING_COURTESY => 'bg-red-500',
        };
    }

    public function iconBg(): string
    {
        return match($this) {
            self::SALE             => 'bg-emerald-50',
            self::WITHDRAWAL       => 'bg-amber-50',
            self::INFLOW           => 'bg-teal-50',
            self::COURTESY         => 'bg-pink-50',
            self::SESSION_OPEN     => 'bg-blue-50',
            self::PENDING_MOVEMENT,
            self::PENDING_COURTESY => 'bg-red-50',
        };
    }

    public function iconClr(): string
    {
        return match($this) {
            self::SALE             => 'text-emerald-600',
            self::WITHDRAWAL       => 'text-amber-600',
            self::INFLOW           => 'text-teal-600',
            self::COURTESY         => 'text-pink-500',
            self::SESSION_OPEN     => 'text-blue-600',
            self::PENDING_MOVEMENT,
            self::PENDING_COURTESY => 'text-red-500',
        };
    }

    public function amountClr(): string
    {
        return match($this) {
            self::SALE         => 'text-emerald-600',
            self::INFLOW       => 'text-teal-600',
            self::WITHDRAWAL   => 'text-amber-600',
            self::COURTESY     => 'text-pink-500',
            self::SESSION_OPEN => 'text-blue-600',
            default            => 'text-red-500',
        };
    }

    /** SVG path d attribute. */
    public function iconPath(): string
    {
        return match($this) {
            self::SALE =>
                'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            self::WITHDRAWAL =>
                'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            self::INFLOW =>
                'M12 4v16m8-8H4',
            self::COURTESY =>
                'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            self::SESSION_OPEN =>
                'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z',
            self::PENDING_MOVEMENT =>
                'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            self::PENDING_COURTESY =>
                'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }
}
