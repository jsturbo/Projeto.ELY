<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sangria extends Model
{
    protected $fillable = ['user_id', 'valor', 'motivo'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
