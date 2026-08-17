<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'color'])]
class Category extends Model
{
    public $timestamps = false;

    protected $attributes = [
        'color' => '#64748b',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** Indica si la categoría tiene gastos asociados (no se puede eliminar si los tiene). */
    public function hasExpenses(): bool
    {
        return $this->expenses()->exists();
    }
}
