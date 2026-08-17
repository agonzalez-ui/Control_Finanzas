<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Resuelve el periodo (mes/año/quincena) activo por defecto en las pantallas
 * de finanzas: si estamos dentro de los primeros 7 días del mes se sugiere
 * todavía el mes anterior (para cerrarlo con calma), y quincena 2; si no,
 * el mes actual y quincena 1.
 */
class Period
{
    public static function suggested(): array
    {
        $today = Carbon::now();

        if ($today->day <= 7) {
            $base = $today->copy()->subMonthNoOverflow()->startOfMonth();

            return [
                'month' => $base->month,
                'year' => $base->year,
                'biweek' => 2,
            ];
        }

        return [
            'month' => $today->month,
            'year' => $today->year,
            'biweek' => 1,
        ];
    }

    /**
     * Resuelve mes/año a partir de un input del usuario (p. ej. filtros de la URL),
     * cayendo al periodo sugerido si el input no es válido.
     */
    public static function resolve(?int $month, ?int $year): array
    {
        $suggested = static::suggested();

        $validMonth = $month >= 1 && $month <= 12;
        $validYear = $year >= 2020 && $year <= 2099;

        return [
            'month' => $validMonth ? $month : $suggested['month'],
            'year' => $validYear ? $year : $suggested['year'],
        ];
    }

    /** Indica si, a falta de filtros explícitos, se está usando el periodo sugerido automáticamente. */
    public static function isUsingSuggested(bool $hasMonthInput, bool $hasYearInput): bool
    {
        return ! $hasMonthInput && ! $hasYearInput && Carbon::now()->day <= 7;
    }
}
