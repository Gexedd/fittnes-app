<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'event_id', 'payload', 'processed_at'])]
class WebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
