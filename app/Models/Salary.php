<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'amount', 'month', 'year'])]
class Salary extends Model
{
    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function byMonth(int $userId, int $month, int $year): ?self
    {
        return static::where('user_id', $userId)->where('month', $month)->where('year', $year)->first();
    }

    /** Inserta o actualiza el salario del periodo. */
    public static function upsertForPeriod(int $userId, int $month, int $year, float $amount): self
    {
        $salary = static::firstOrNew([
            'user_id' => $userId,
            'month' => $month,
            'year' => $year,
        ]);

        $salary->amount = $amount;
        $salary->save();

        return $salary;
    }
}
