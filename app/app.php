<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GerenciarController extends Controller
{
    private function soGerente()
    {
        if (Auth::user()->role !== 'gerente') abort(403);
    }

    public function mesas()
    {
        $this->soGerente();
        $mesas = Table::orderBy('numero')->get();
        return view('gerenciar.mesas', compact('mesas'));
    }

    public function cardapio()
    {
        $this->soGerente();
        $itens      = MenuItem::with('category','stockItem')->orderBy('category_id')->get();
        $categorias = Category::orderBy('nome')->get();
        $estoque    = StockItem::orderBy('nome')->get();
        return view('gerenciar.cardapio', compact('itens','categorias','estoque'));
    }

    public function cardapioStore(Request $request)
    {
        $this->soGerente();
        $v = $request->validate([
            'nome'         => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'preco'        => 'required|numeric|min:0.01',
            'descricao'    => 'nullable|string|max:500',
            'stock_item_id'=> 'nullable|exists:stock_items,id',
            'disponivel'   => 'nullable|boolean',
        ]);
        $v['disponivel'] = $request->has('disponivel');
        MenuItem::create($v);
        return back()->with('success', '✅ Item adicionado ao cardápio!');
    }

    public function cardapioUpdate(Request $request, MenuItem $item)
    {
        $this->soGerente();
        $v = $request->validate([
            'nome'         => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'preco'        => 'required|numeric|min:0.01',
            'descricao'    => 'nullable|string|max:500',
            'stock_item_id'=> 'nullable|exists:stock_items,id',
            'disponivel'   => 'nullable|boolean',
        ]);
        $v['disponivel'] = $request->has('disponivel');
        $item->update($v);
        return back()->with('success', '✅ Item atualizado!');
    }

    public function cardapioDestroy(MenuItem $item)
    {
        $this->soGerente();
        $item->delete();
        return back()->with('success', '✅ Item removido do cardápio!');
    }

    public function funcionarios()
    {
        $this->soGerente();
        $usuarios = User::orderBy('role')->orderBy('name')->get();
        return view('gerenciar.funcionarios', compact('usuarios'));
    }

    public function produtos()
    {
        $this->soGerente();
        $itens = StockItem::orderBy('nome')->get();
        return view('gerenciar.produtos', compact('itens'));
    }

    public function produtosStore(Request $request)
    {
        $this->soGerente();
        $v = $request->validate([
            'nome'             => 'required|string|max:255',
            'unidade'          => 'required|string|max:20',
            'quantidade_atual' => 'required|numeric|min:0',
            'quantidade_minima'=> 'required|numeric|min:0',
            'preco_unitario'   => 'required|numeric|min:0',
        ]);
        StockItem::create($v);
        return back()->with('success', '✅ Produto cadastrado!');
    }

    public function produtosUpdate(Request $request, StockItem $item)
    {
        $this->soGerente();
        $v = $request->validate([
            'nome'             => 'required|string|max:255',
            'unidade'          => 'required|string|max:20',
            'quantidade_minima'=> 'required|numeric|min:0',
            'preco_unitario'   => 'required|numeric|min:0',
        ]);
        $item->update($v);
        return back()->with('success', '✅ Produto atualizado!');
    }

    public function produtosDestroy(StockItem $item)
    {
        $this->soGerente();
        $item->delete();
        return back()->with('success', '✅ Produto removido!');
    }
}
