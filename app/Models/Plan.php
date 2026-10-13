<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'level',
    'price_clp',
    'mp_plan_id',
    'description',
    'features',
    'is_active',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'price_clp' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Precio formateado en pesos chilenos, p. ej. "$14.990".
     */
    public function priceFormatted(): string
    {
        return '$'.number_format($this->price_clp, 0, ',', '.');
    }

    /**
     * Videos cuyo nivel mínimo es exactamente el de este plan.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'min_plan_level', 'level');
    }
}
