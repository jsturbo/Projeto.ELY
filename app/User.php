<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['table_id', 'user_id', 'status', 'total', 'observacoes', 'horario_pedido', 'horario_pronto', 'horario_entrega', 'horario_termino_preparo'];

    protected $casts = [
        'horario_pedido' => 'datetime',
        'horario_pronto' => 'datetime',
        'horario_entrega' => 'datetime',
        'horario_termino_preparo' => 'datetime',
        'total' => 'decimal:2',
    ];

    // Define horario_pedido automaticamente ao criar o pedido
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->horario_pedido)) {
                $order->horario_pedido = now();
            }
        });
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
