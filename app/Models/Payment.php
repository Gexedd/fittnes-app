<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subscription_id',
    'mp_payment_id',
    'amount_clp',
    'status',
    'paid_at',
])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount_clp' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function amountFormatted(): string
    {
        return '$'.number_format($this->amount_clp, 0, ',', '.');
    }
}
