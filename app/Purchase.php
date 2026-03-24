<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'menu_item_id', 'quantidade', 'preco_unitario', 'subtotal', 'observacoes', 'status', 'horario_pronto'];

    protected $casts = [
        'horario_pronto' => 'datetime',
    ];

    // Status padrão ao criar um item
    protected $attributes = [
        'status' => 'pendente',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
