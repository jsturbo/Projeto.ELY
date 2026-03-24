<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\Category;
use App\Models\StockItem;

echo "Adicionando novos pratos baseado no estoque disponível...\n\n";

// Obter categoria padrão (Pratos Principais)
$category = Category::where('nome', 'Pratos Principais')->first();
if (!$category) {
    $category = Category::create([
        'nome' => 'Pratos Principais',
        'descricao' => 'Pratos principais do restaurante'
    ]);
    echo "✓ Categoria 'Pratos Principais' criada\n";
}

// Obter itens de estoque
$frango = StockItem::where('nome', 'Frango Grelhado')->first();
$arroz = StockItem::where('nome', 'Arroz')->first();
$feijao = StockItem::where('nome', 'Feijão')->first();
$carne = StockItem::where('nome', 'Carne Vermelha')->first();
$batata = StockItem::where('nome', 'Batata')->first();
$refrigerante = StockItem::where('nome', 'Refrigerante')->first();

// Novos pratos a criar
$novosPratos = [
    // Frango
    [
        'nome' => 'Salada de Frango',
        'descricao' => 'Salada fresca com peito de frango grelhado',
        'preco' => 28.50,
        'stock_item_id' => $frango?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Frango à Milanesa',
        'descricao' => 'Frango empanado e frito, crocante por fora',
        'preco' => 32.00,
        'stock_item_id' => $frango?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Frango Assado',
        'descricao' => 'Frango assado inteiro ou em pedaços',
        'preco' => 35.50,
        'stock_item_id' => $frango?->id,
        'disponivel' => true
    ],

    // Arroz
    [
        'nome' => 'Arroz com Frango',
        'descricao' => 'Arroz preparado com frango desfiado',
        'preco' => 26.00,
        'stock_item_id' => $arroz?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Arroz Integral',
        'descricao' => 'Arroz integral nutritivo',
        'preco' => 18.00,
        'stock_item_id' => $arroz?->id,
        'disponivel' => true
    ],

    // Feijão
    [
        'nome' => 'Feijoada',
        'descricao' => 'Feijoada tradicional brasileira',
        'preco' => 38.00,
        'stock_item_id' => $feijao?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Feijão Tropeiro',
        'descricao' => 'Feijão com bacon e farinha de milho',
        'preco' => 22.00,
        'stock_item_id' => $feijao?->id,
        'disponivel' => true
    ],

    // Carne Vermelha
    [
        'nome' => 'Alcatra na Chapa',
        'descricao' => 'Alcatra grelhada na chapa',
        'preco' => 42.00,
        'stock_item_id' => $carne?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Carne de Sol',
        'descricao' => 'Carne de sol desfiada',
        'preco' => 36.50,
        'stock_item_id' => $carne?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Bife à Italiana',
        'descricao' => 'Bife com molho à italiana',
        'preco' => 40.00,
        'stock_item_id' => $carne?->id,
        'disponivel' => true
    ],

    // Batata
    [
        'nome' => 'Batata Frita',
        'descricao' => 'Batata frita crocante',
        'preco' => 14.50,
        'stock_item_id' => $batata?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Purê de Batata',
        'descricao' => 'Purê cremoso de batata',
        'preco' => 12.00,
        'stock_item_id' => $batata?->id,
        'disponivel' => true
    ],
    [
        'nome' => 'Batata Gratinada',
        'descricao' => 'Batata com queijo gratinado',
        'preco' => 18.50,
        'stock_item_id' => $batata?->id,
        'disponivel' => true
    ],

    // Refrigerante/Bebida
    [
        'nome' => 'Refrigerante',
        'descricao' => 'Refrigerante gelado 2L',
        'preco' => 8.50,
        'stock_item_id' => $refrigerante?->id,
        'disponivel' => true
    ],
];

$contador = 0;
foreach ($novosPratos as $prato) {
    // Verificar se o prato já existe
    $existe = MenuItem::where('nome', $prato['nome'])->first();

    if (!$existe) {
        MenuItem::create([
            'category_id' => $category->id,
            'nome' => $prato['nome'],
            'descricao' => $prato['descricao'],
            'preco' => $prato['preco'],
            'stock_item_id' => $prato['stock_item_id'],
            'disponivel' => $prato['disponivel']
        ]);
        echo "✓ '{$prato['nome']}' - R$ {$prato['preco']}\n";
        $contador++;
    }
}

echo "\n✅ {$contador} novos pratos adicionados com sucesso!\n";
