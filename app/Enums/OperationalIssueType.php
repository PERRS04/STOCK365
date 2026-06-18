<?php

namespace App\Enums;

enum OperationalIssueType: string
{
    case PENDING_CLOSING_APPROVAL = 'pending_closing_approval';
    case ABANDONED_SESSION        = 'abandoned_session';
    case MISSING_CLOSING          = 'missing_closing';
    case CASH_DIFFERENCE          = 'cash_difference';
    case SESSION_TIMEOUT          = 'session_timeout';

    public function message(): string
    {
        return match($this) {
            self::PENDING_CLOSING_APPROVAL =>
                'Tienes un cierre de caja enviado y pendiente de aprobación por el supervisor.',
            self::ABANDONED_SESSION =>
                'Tu sesión de caja lleva demasiado tiempo abierta sin registrar cierre.',
            self::MISSING_CLOSING =>
                'Se detectó una sesión de caja anterior sin cierre formal registrado.',
            self::CASH_DIFFERENCE =>
                'Se detectó una diferencia crítica en el último cierre rechazado.',
            self::SESSION_TIMEOUT =>
                'Tu sesión operacional expiró por inactividad prolongada.',
        };
    }

    public function resolution(): string
    {
        return match($this) {
            self::PENDING_CLOSING_APPROVAL =>
                'Espera a que el supervisor apruebe o rechace tu cierre. Si fue rechazado, la caja se reabrirá automáticamente y podrás continuar.',
            self::ABANDONED_SESSION =>
                'Tu sesión sigue activa pero excedió el tiempo operacional permitido. Debes registrar y enviar el cierre de caja para que el supervisor lo revise. Una vez aprobado o corregido, el acceso será restaurado automáticamente.',
            self::MISSING_CLOSING =>
                'Comunícate con el supervisor para regularizar el cierre de la sesión anterior.',
            self::CASH_DIFFERENCE =>
                'El supervisor revisará la diferencia detectada. Mantente disponible para aclarar el cuadre.',
            self::SESSION_TIMEOUT =>
                'Solicita al supervisor que autorice la reapertura de tu sesión operacional.',
        };
    }
}
