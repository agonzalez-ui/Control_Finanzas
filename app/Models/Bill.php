<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cuenta por pagar a plazos (ej. financiamiento de un refrigerador o TV):
 * un monto total dividido en cuotas mensuales que se van marcando como
 * pagadas periodo a periodo.
 */
#[Fillable(['user_id', 'name', 'description', 'total_amount', 'installments', 'installment_amount', 'due_day', 'start_date', 'paid_installments', 'completed'])]
class Bill extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /** Cuentas activas (no completadas) cuyo plazo ya inició para el periodo dado. */
    public static function dueInPeriod(int $userId, int $month, int $year)
    {
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        return static::where('user_id', $userId)
            ->where('completed', false)
            ->whereDate('start_date', '<=', $periodEnd)
            ->with(['payments' => fn ($q) => $q->where('month', $month)->where('year', $year)])
            ->orderBy('due_day')
            ->get();
    }

    /** Total de cuotas pendientes de las cuentas activas en el periodo (para restar del disponible). */
    public static function totalDueInPeriod(int $userId, int $month, int $year): float
    {
        return static::dueInPeriod($userId, $month, $year)
            ->reject(fn (self $bill) => $bill->isPaidForPeriod($month, $year))
            ->sum('installment_amount');
    }

    public function isPaidForPeriod(int $month, int $year): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(fn (BillPayment $p) => $p->month === $month && $p->year === $year);
        }

        return $this->payments()->where('month', $month)->where('year', $year)->exists();
    }

    public function remainingAmount(): float
    {
        return max(0.0, (float) $this->total_amount - ($this->paid_installments * (float) $this->installment_amount));
    }

    public function progressPercent(): int
    {
        if ($this->installments === 0) {
            return 0;
        }

        return (int) min(100, round(($this->paid_installments / $this->installments) * 100));
    }

    /** Marca la cuota del periodo como pagada (idempotente: no duplica si ya existe). */
    public function markPaid(int $month, int $year, ?string $date = null): ?BillPayment
    {
        if ($this->payments()->where('month', $month)->where('year', $year)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($month, $year, $date) {
            $payment = $this->payments()->create([
                'user_id' => $this->user_id,
                'amount' => $this->installment_amount,
                'date' => $date ?: now()->toDateString(),
                'month' => $month,
                'year' => $year,
            ]);

            $this->increment('paid_installments');

            if ($this->paid_installments >= $this->installments) {
                $this->update(['completed' => true]);
            }

            return $payment;
        });
    }

    /** Revierte el pago de un periodo (borra el registro y descuenta la cuota). */
    public function undoPayment(BillPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->delete();
            $this->decrement('paid_installments');
            $this->update(['completed' => false]);
        });
    }
}
