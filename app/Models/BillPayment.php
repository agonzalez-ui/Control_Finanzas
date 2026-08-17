<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bill_id', 'user_id', 'amount', 'date', 'month', 'year'])]
class BillPayment extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
