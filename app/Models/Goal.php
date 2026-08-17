<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'description', 'target_amount', 'current_amount', 'due_date', 'completed'])]
class Goal extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Abona a la meta; la marca como completada si se alcanza el objetivo. */
    public function contribute(float $amount): void
    {
        $this->current_amount += $amount;
        $this->completed = $this->current_amount >= $this->target_amount;
        $this->save();
    }

    public function progressPercent(): int
    {
        if ((float) $this->target_amount === 0.0) {
            return 0;
        }

        return (int) min(100, round(($this->current_amount / $this->target_amount) * 100));
    }
}
