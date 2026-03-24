<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = ['numero', 'assentos', 'status', 'garcom_id'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function garcom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'garcom_id');
    }
}

