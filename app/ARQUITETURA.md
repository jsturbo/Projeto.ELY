<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\StockItem;
use App\Models\MenuItem;

echo "Adicionando itens ao estoque...\n";

// Criar itens de estoque
$items = [
    [
        'nome' => 'Frango Grelhado',
        'descricao' => 'Peito de frango fresco',
        'unidade' => 'kg',
        'quantidade_atual' => 50,
        'quantidade_minima' => 10,
        'preco_unitario' => 8.50
    ],
    [
        'nome' => 'Arroz',
        'descricao' => 'Arroz branco integral',
        'unidade' => 'kg',
        'quantidade_atual' => 30,
        'quantidade_minima' => 5,
        'preco_unitario' => 2.50
    ],
    [
        'nome' => 'Feijão',
        'descricao' => 'Feijão carioca',
        'unidade' => 'kg',
        'quantidade_atual' => 25,
        'quantidade_minima' => 5,
        'preco_unitario' => 3.00
    ],
    [
        'nome' => 'Carne Vermelha',
        'descricao' => 'Carne bovina maturada',
        'unidade' => 'kg',
        'quantidade_atual' => 40,
        'quantidade_minima' => 10,
        'preco_unitario' => 12.50
    ],
    [
        'nome' => 'Batata',
        'descricao' => 'Batata inglesa',
        'unidade' => 'kg',
        'quantidade_atual' => 35,
        'quantidade_minima' => 10,
        'preco_unitario' => 1.50
    ],
    [
        'nome' => 'Refrigerante',
        'descricao' => 'Refrigerante 2L',
        'unidade' => 'un',
        'quantidade_atual' => 100,
        'quantidade_minima' => 20,
        'preco_unitario' => 3.50
    ]
];

foreach ($items as $item) {
    StockItem::updateOrCreate(
        ['nome' => $item['nome']],
        $item
    );
    echo "✓ " . $item['nome'] . " adicionado ao estoque\n";
}

// Associar itens de estoque aos menu items
echo "\nAssociando itens de estoque aos itens do menu...\n";

$associations = [
    'Frango' => 'Frango Grelhado',
    'Arroz' => 'Arroz',
    'Feijão' => 'Feijão',
    'Carne' => 'Carne Vermelha',
    'Batata' => 'Batata',
    'Refrigerante' => 'Refrigerante',
];

foreach ($associations as $menuItemName => $stockItemName) {
    $menuItem = MenuItem::where('nome', 'like', "%$menuItemName%")->first();
    $stockItem = StockItem::where('nome', $stockItemName)->first();

    if ($menuItem && $stockItem) {
        $menuItem->update(['stock_item_id' => $stockItem->id]);
        echo "✓ " . $menuItem->nome . " vinculado a " . $stockItem->nome . "\n";
    }
}

echo "\n✅ Estoque preparado com sucesso!\n";
