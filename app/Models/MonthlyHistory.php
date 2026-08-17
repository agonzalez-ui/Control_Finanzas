<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'month', 'year', 'salary', 'total_expenses', 'total_savings', 'total_subscriptions'])]
class MonthlyHistory extends Model
{
    public $timestamps = false;

    protected $table = 'monthly_history';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forPeriod(int $userId, int $month, int $year): ?self
    {
        return static::where('user_id', $userId)->where('month', $month)->where('year', $year)->first();
    }

    public static function lastMonths(int $userId, int $limit = 6)
    {
        return static::where('user_id', $userId)->orderByDesc('year')->orderByDesc('month')->limit($limit)->get();
    }

    /** Crea un snapshot del periodo. Retorna null si ese mes ya había sido cerrado. */
    public static function closePeriod(
        int $userId,
        int $month,
        int $year,
        float $salary,
        float $totalExpenses,
        float $totalSavings,
        float $totalSubscriptions,
    ): ?self {
        if (static::forPeriod($userId, $month, $year)) {
            return null;
        }

        return static::create([
            'user_id' => $userId,
            'month' => $month,
            'year' => $year,
            'salary' => $salary,
            'total_expenses' => $totalExpenses,
            'total_savings' => $totalSavings,
            'total_subscriptions' => $totalSubscriptions,
        ]);
    }
}
