<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['saving_account_id', 'user_id', 'amount', 'description', 'date', 'biweek', 'month', 'year', 'affects_available'])]
class SavingMovement extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'affects_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savingAccount(): BelongsTo
    {
        return $this->belongsTo(SavingAccount::class);
    }

    public static function netByPeriod(int $userId, int $month, int $year): float
    {
        return (float) static::where('user_id', $userId)->where('month', $month)->where('year', $year)
            ->where('affects_available', true)->sum('amount');
    }

    public static function netByQuincena(int $userId, int $month, int $year, int $biweek): float
    {
        return (float) static::where('user_id', $userId)->where('month', $month)->where('year', $year)
            ->where('biweek', $biweek)->where('affects_available', true)->sum('amount');
    }

    public static function recentByUser(int $userId, int $limit = 50)
    {
        return static::with('savingAccount')->where('user_id', $userId)->orderByDesc('date')->limit($limit)->get();
    }

    /** Crea el movimiento y ajusta (suma) el saldo de la cuenta en la misma transacción. */
    public static function record(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $movement = static::create($attributes);

            SavingAccount::where('id', $attributes['saving_account_id'])
                ->where('user_id', $attributes['user_id'])
                ->increment('balance', $attributes['amount']);

            return $movement;
        });
    }

    /** Elimina el movimiento revirtiendo el saldo de la cuenta. */
    public function deleteAndRevert(): void
    {
        DB::transaction(function () {
            SavingAccount::where('id', $this->saving_account_id)->decrement('balance', $this->amount);
            $this->delete();
        });
    }
}
