<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Table;
use App\Models\Category;
use App\Models\StockItem;
use App\Models\MenuItem;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── USUÁRIOS ──────────────────────────────────────────────
        $usuarios = [
            ['name' => 'Gerente João',  'email' => 'gerente@restaurante.com', 'role' => 'gerente'],
            ['name' => 'João Garçom',   'email' => 'garcom@restaurante.com',  'role' => 'garcom'],
            ['name' => 'Chef Pedro',    'email' => 'chef@restaurante.com',    'role' => 'chef'],
            ['name' => 'Maria Caixa',   'email' => 'caixa@restaurante.com',   'role' => 'caixa'],
        ];
        foreach ($usuarios as $u) {
            User::firstOrCreate(['email' => $u['email']], array_merge($u, [
                'password' => Hash::make('password'),
                'ativo'    => true,
            ]));
        }

        // ── MESAS ─────────────────────────────────────────────────
        $configMesas = [
            1=>2, 2=>2, 3=>4, 4=>4, 5=>4,
            6=>6, 7=>6, 8=>8, 9=>4, 10=>4,
        ];
        foreach ($configMesas as $num => $assentos) {
            Table::firstOrCreate(['numero' => $num], [
                'assentos' => $assentos,
                'status'   => 'disponivel',
            ]);
        }

        // ── ESTOQUE (ingredientes) ────────────────────────────────
        $estoqueData = [
            // Carnes
            ['nome' => 'Frango',          'unidade' => 'kg',  'qtd' => 50,  'min' => 10, 'preco' => 9.50],
            ['nome' => 'Carne Vermelha',  'unidade' => 'kg',  'qtd' => 40,  'min' => 10, 'preco' => 18.00],
            ['nome' => 'Peixe Tilápia',   'unidade' => 'kg',  'qtd' => 20,  'min' => 5,  'preco' => 14.00],
            ['nome' => 'Camarão',         'unidade' => 'kg',  'qtd' => 15,  'min' => 5,  'preco' => 35.00],
            ['nome' => 'Costela Suína',   'unidade' => 'kg',  'qtd' => 25,  'min' => 5,  'preco' => 16.00],
            // Grãos e massas
            ['nome' => 'Arroz',           'unidade' => 'kg',  'qtd' => 80,  'min' => 20, 'preco' => 3.00],
            ['nome' => 'Feijão',          'unidade' => 'kg',  'qtd' => 40,  'min' => 10, 'preco' => 4.50],
            ['nome' => 'Macarrão',        'unidade' => 'kg',  'qtd' => 30,  'min' => 10, 'preco' => 5.00],
            ['nome' => 'Farinha',         'unidade' => 'kg',  'qtd' => 20,  'min' => 5,  'preco' => 2.50],
            // Vegetais
            ['nome' => 'Batata',          'unidade' => 'kg',  'qtd' => 60,  'min' => 15, 'preco' => 2.00],
            ['nome' => 'Alface',          'unidade' => 'un',  'qtd' => 30,  'min' => 10, 'preco' => 1.50],
            ['nome' => 'Tomate',          'unidade' => 'kg',  'qtd' => 20,  'min' => 5,  'preco' => 4.00],
            ['nome' => 'Queijo',          'unidade' => 'kg',  'qtd' => 10,  'min' => 3,  'preco' => 22.00],
            // Bebidas
            ['nome' => 'Refrigerante Lata','unidade' => 'un', 'qtd' => 150, 'min' => 30, 'preco' => 2.50],
            ['nome' => 'Suco Natural',    'unidade' => 'un',  'qtd' => 60,  'min' => 20, 'preco' => 3.00],
            ['nome' => 'Água Mineral',    'unidade' => 'un',  'qtd' => 200, 'min' => 50, 'preco' => 1.00],
            ['nome' => 'Cerveja',         'unidade' => 'un',  'qtd' => 120, 'min' => 30, 'preco' => 3.50],
            ['nome' => 'Café',            'unidade' => 'kg',  'qtd' => 10,  'min' => 2,  'preco' => 28.00],
            // Sobremesas
            ['nome' => 'Chocolate',       'unidade' => 'kg',  'qtd' => 8,   'min' => 2,  'preco' => 20.00],
            ['nome' => 'Sorvete',         'unidade' => 'kg',  'qtd' => 15,  'min' => 5,  'preco' => 15.00],
            ['nome' => 'Açúcar',          'unidade' => 'kg',  'qtd' => 20,  'min' => 5,  'preco' => 3.50],
        ];

        $stock = [];
        foreach ($estoqueData as $e) {
            $stock[$e['nome']] = StockItem::firstOrCreate(['nome' => $e['nome']], [
                'unidade'           => $e['unidade'],
                'quantidade_atual'  => $e['qtd'],
                'quantidade_minima' => $e['min'],
                'preco_unitario'    => $e['preco'],
            ]);
        }

        // ── CATEGORIAS E PRATOS ────────────────────────────────────
        $catPratos    = Category::firstOrCreate(['nome' => 'Pratos Principais'],  ['descricao' => 'Pratos quentes e completos']);
        $catGrelhados = Category::firstOrCreate(['nome' => 'Grelhados'],          ['descricao' => 'Carnes e peixes na grelha']);
        $catMassas    = Category::firstOrCreate(['nome' => 'Massas e Acompanhos'],['descricao' => 'Massas, arroz e feijão']);
        $catEntradas  = Category::firstOrCreate(['nome' => 'Entradas'],           ['descricao' => 'Petiscos e entradas']);
        $catBebidas   = Category::firstOrCreate(['nome' => 'Bebidas'],            ['descricao' => 'Bebidas frias e quentes']);
        $catSobremesa = Category::firstOrCreate(['nome' => 'Sobremesas'],         ['descricao' => 'Doces e sobremesas']);

        $menu = [
            // ── Pratos Principais
            ['cat' => $catPratos, 'nome' => 'Feijoada Completa',        'preco' => 42.00, 'stock' => 'Feijão',          'desc' => 'Feijoada com arroz, couve e farofa'],
            ['cat' => $catPratos, 'nome' => 'Frango ao Molho Pardo',    'preco' => 34.00, 'stock' => 'Frango',          'desc' => 'Frango caipira com arroz e macarrão'],
            ['cat' => $catPratos, 'nome' => 'Peixe à Baiana',           'preco' => 46.00, 'stock' => 'Peixe Tilápia',   'desc' => 'Tilápia ao molho de tomate com arroz'],
            ['cat' => $catPratos, 'nome' => 'Moqueca de Camarão',       'preco' => 58.00, 'stock' => 'Camarão',         'desc' => 'Camarão no leite de coco com pirão'],
            ['cat' => $catPratos, 'nome' => 'Costela no Bafo',          'preco' => 52.00, 'stock' => 'Costela Suína',   'desc' => 'Costela suína assada lentamente'],

            // ── Grelhados
            ['cat' => $catGrelhados, 'nome' => 'Frango Grelhado',       'preco' => 32.00, 'stock' => 'Frango',          'desc' => 'Peito de frango grelhado com salada'],
            ['cat' => $catGrelhados, 'nome' => 'Alcatra na Chapa',      'preco' => 48.00, 'stock' => 'Carne Vermelha',  'desc' => 'Alcatra 300g com batata frita'],
            ['cat' => $catGrelhados, 'nome' => 'Peixe Grelhado',        'preco' => 40.00, 'stock' => 'Peixe Tilápia',   'desc' => 'Tilápia grelhada com legumes'],
            ['cat' => $catGrelhados, 'nome' => 'Camarão na Manteiga',   'preco' => 52.00, 'stock' => 'Camarão',         'desc' => 'Camarão grelhado com manteiga de alho'],
            ['cat' => $catGrelhados, 'nome' => 'Carne de Sol',          'preco' => 44.00, 'stock' => 'Carne Vermelha',  'desc' => 'Carne de sol com manteiga de garrafa'],

            // ── Massas e Acompanhos
            ['cat' => $catMassas, 'nome' => 'Macarrão à Bolonhesa',     'preco' => 30.00, 'stock' => 'Macarrão',        'desc' => 'Macarrão com molho de carne moída'],
            ['cat' => $catMassas, 'nome' => 'Macarrão ao Alho e Óleo',  'preco' => 24.00, 'stock' => 'Macarrão',        'desc' => 'Macarrão com azeite e alho dourado'],
            ['cat' => $catMassas, 'nome' => 'Arroz com Feijão',         'preco' => 12.00, 'stock' => 'Arroz',           'desc' => 'Arroz branco e feijão temperado'],
            ['cat' => $catMassas, 'nome' => 'Batata Frita',             'preco' => 18.00, 'stock' => 'Batata',          'desc' => 'Batata frita crocante com sal'],
            ['cat' => $catMassas, 'nome' => 'Batata Rústica',           'preco' => 20.00, 'stock' => 'Batata',          'desc' => 'Batata rústica com tempero especial'],

            // ── Entradas
            ['cat' => $catEntradas, 'nome' => 'Salada Verde',           'preco' => 16.00, 'stock' => 'Alface',          'desc' => 'Alface, tomate e rúcula com vinagrete'],
            ['cat' => $catEntradas, 'nome' => 'Salada Caprese',         'preco' => 22.00, 'stock' => 'Queijo',          'desc' => 'Tomate, muçarela e manjericão'],
            ['cat' => $catEntradas, 'nome' => 'Frango à Milanesa',      'preco' => 28.00, 'stock' => 'Frango',          'desc' => 'Frango empanado crocante'],
            ['cat' => $catEntradas, 'nome' => 'Isca de Peixe',          'preco' => 30.00, 'stock' => 'Peixe Tilápia',   'desc' => 'Iscas de tilápia empanadas'],
            ['cat' => $catEntradas, 'nome' => 'Dadinho de Tapioca',     'preco' => 24.00, 'stock' => 'Farinha',         'desc' => 'Dadinho de tapioca com geleia de pimenta'],

            // ── Bebidas
            ['cat' => $catBebidas, 'nome' => 'Refrigerante',            'preco' =>  8.00, 'stock' => 'Refrigerante Lata','desc' => 'Coca-Cola, Guaraná ou Sprite (350ml)'],
            ['cat' => $catBebidas, 'nome' => 'Suco Natural',            'preco' => 12.00, 'stock' => 'Suco Natural',    'desc' => 'Laranja, limão ou maracujá'],
            ['cat' => $catBebidas, 'nome' => 'Água Mineral',            'preco' =>  5.00, 'stock' => 'Água Mineral',    'desc' => 'Água sem ou com gás 500ml'],
            ['cat' => $catBebidas, 'nome' => 'Cerveja',                 'preco' => 10.00, 'stock' => 'Cerveja',         'desc' => 'Cerveja gelada long neck'],
            ['cat' => $catBebidas, 'nome' => 'Café Expresso',           'preco' =>  6.00, 'stock' => 'Café',            'desc' => 'Café expresso curto ou longo'],

            // ── Sobremesas
            ['cat' => $catSobremesa, 'nome' => 'Mousse de Chocolate',   'preco' => 14.00, 'stock' => 'Chocolate',       'desc' => 'Mousse cremoso de chocolate belga'],
            ['cat' => $catSobremesa, 'nome' => 'Sorvete 2 Bolas',       'preco' => 12.00, 'stock' => 'Sorvete',         'desc' => 'Duas bolas de sorvete à escolha'],
            ['cat' => $catSobremesa, 'nome' => 'Pudim de Leite',        'preco' => 13.00, 'stock' => 'Açúcar',          'desc' => 'Pudim com calda de caramelo'],
            ['cat' => $catSobremesa, 'nome' => 'Brownie com Sorvete',   'preco' => 18.00, 'stock' => 'Chocolate',       'desc' => 'Brownie quente com bola de sorvete'],
        ];

        foreach ($menu as $m) {
            $mi = MenuItem::firstOrCreate(['nome' => $m['nome']], [
                'category_id'   => $m['cat']->id,
                'stock_item_id' => $stock[$m['stock']]->id ?? null,
                'preco'         => $m['preco'],
                'descricao'     => $m['desc'],
                'disponivel'    => true,
            ]);

            // Vincular ingrediente com quantidade por porção
            if (isset($stock[$m['stock']]) && $mi->ingredients()->count() === 0) {
                $mi->ingredients()->create([
                    'stock_item_id'    => $stock[$m['stock']]->id,
                    'quantidade'       => $m['qtd_porcao'] ?? 0.3,
                    'quantidade_gramas'=> ($m['qtd_porcao'] ?? 0.3) * 1000,
                ]);
            }
        }
    }
}
