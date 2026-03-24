<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'stock_item_id',
        'user_id',
        'quantidade',
        'preco_unitario',
        'total',
        'fornecedor',
        'observacoes',
        'status',
        'data_entrega',
    ];

    protected function casts(): array
    {
        return [
            'data_entrega' => 'date',
        ];
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
