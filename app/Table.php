<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = ['nome', 'descricao', 'unidade', 'quantidade_atual', 'quantidade_minima', 'preco_unitario', 'unidade_original', 'usa_gramas'];

    public function movimentos(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(MenuItemIngredient::class);
    }
}
