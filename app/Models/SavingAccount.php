<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'type', 'balance'])]
class SavingAccount extends Model
{
    public $timestamps = false;

    protected $attributes = [
        'type' => 'banco',
        'balance' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SavingMovement::class);
    }

    public static function totalSavings(int $userId): float
    {
        return (float) static::where('user_id', $userId)->sum('balance');
    }
}
