<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'amount', 'billing_day', 'active'])]
class Subscription extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function totalActive(int $userId): float
    {
        return (float) static::where('user_id', $userId)->where('active', true)->sum('amount');
    }

    public function toggle(): void
    {
        $this->update(['active' => ! $this->active]);
    }
}
