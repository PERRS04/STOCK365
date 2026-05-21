<?php

if (!function_exists('formatCurrency')) {
    function formatCurrency(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        if ($value < 0) {
            return '-$' . number_format(abs($value), 2, '.', ',');
        }
        return '$' . number_format($value, 2, '.', ',');
    }
}
