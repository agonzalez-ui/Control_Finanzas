<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'category_id', 'description', 'amount', 'date', 'biweek', 'month', 'year'])]
class Expense extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static function byMonth(int $userId, int $month, int $year)
    {
        return static::with('category')
            ->where('user_id', $userId)->where('month', $month)->where('year', $year)
            ->orderByDesc('date')
            ->get();
    }

    public static function byQuincena(int $userId, int $month, int $year, int $biweek)
    {
        return static::with('category')
            ->where('user_id', $userId)->where('month', $month)->where('year', $year)->where('biweek', $biweek)
            ->orderByDesc('date')
            ->get();
    }

    public static function totalByMonth(int $userId, int $month, int $year): float
    {
        return (float) static::where('user_id', $userId)->where('month', $month)->where('year', $year)->sum('amount');
    }

    public static function totalByQuincena(int $userId, int $month, int $year, int $biweek): float
    {
        return (float) static::where('user_id', $userId)->where('month', $month)->where('year', $year)
            ->where('biweek', $biweek)->sum('amount');
    }

    /** Gastos agrupados por categoría en un periodo (solo categorías con gasto > 0), de mayor a menor. */
    public static function byCategory(int $userId, int $month, int $year)
    {
        return Category::where('user_id', $userId)
            ->withSum(['expenses as total' => function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            }], 'amount')
            ->get()
            ->filter(fn ($category) => (float) $category->total > 0)
            ->sortByDesc('total')
            ->values();
    }

    public static function quincenaFromDate(string|Carbon $date): int
    {
        return Carbon::parse($date)->day <= 15 ? 1 : 2;
    }

    /** Resumen de totales por mes, más reciente primero (para gráfico de tendencia). */
    public static function monthlySummary(int $userId, int $limit = 6)
    {
        return static::selectRaw('month, year, COALESCE(SUM(amount), 0) as total')
            ->where('user_id', $userId)
            ->groupBy('year', 'month')
            ->orderByDesc('year')->orderByDesc('month')
            ->limit($limit)
            ->get();
    }
}
