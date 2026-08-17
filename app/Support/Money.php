<?php

namespace App\Support;

class Money
{
    public const CURRENCY = '₡';

    private const MONTH_NAMES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** Formatea un monto en colones sin decimales, con punto como separador de miles. */
    public static function format(float $amount): string
    {
        return self::CURRENCY.' '.number_format($amount, 0, ',', '.');
    }

    public static function monthName(int $month): string
    {
        return self::MONTH_NAMES[$month] ?? '';
    }
}
