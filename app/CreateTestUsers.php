<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControleEstoqueController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'gerente') abort(403);

        $itens = StockItem::orderBy('nome')->get();

        $di = request('data_inicio')
            ? Carbon::parse(request('data_inicio'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();
        $df = request('data_fim')
            ? Carbon::parse(request('data_fim'))->endOfDay()
            : Carbon::today()->endOfDay();

        // Entradas no período
        $entradas = StockMovement::with('stockItem','user')
            ->where('tipo','entrada')
            ->whereBetween('created_at',[$di,$df])
            ->orderByDesc('created_at')
            ->get();

        // Saídas no período
        $saidas = StockMovement::with('stockItem','user')
            ->where('tipo','saida')
            ->whereBetween('created_at',[$di,$df])
            ->orderByDesc('created_at')
            ->get();

        // Ajustes no período
        $ajustes = StockMovement::with('stockItem','user')
            ->where('tipo','ajuste')
            ->whereBetween('created_at',[$di,$df])
            ->orderByDesc('created_at')
            ->get();

        // Saldo atual por item
        $saldo = $itens->map(function($item) use ($di, $df) {
            $entTotal = StockMovement::where('stock_item_id',$item->id)->where('tipo','entrada')->whereBetween('created_at',[$di,$df])->sum('quantidade');
            $saiTotal = StockMovement::where('stock_item_id',$item->id)->where('tipo','saida')->whereBetween('created_at',[$di,$df])->sum('quantidade');
            return [
                'item'     => $item,
                'entradas' => $entTotal,
                'saidas'   => $saiTotal,
                'saldo'    => $item->quantidade_atual,
                'valor'    => $item->quantidade_atual * $item->preco_unitario,
            ];
        });

        $totalEntradas = $entradas->sum('quantidade');
        $totalSaidas   = $saidas->sum('quantidade');
        $valorEstoque  = $itens->sum(fn($i) => $i->quantidade_atual * $i->preco_unitario);

        return view('controle.estoque', compact(
            'itens','entradas','saidas','ajustes','saldo',
            'totalEntradas','totalSaidas','valorEstoque','di','df'
        ));
    }

    public function entrada(Request $request)
    {
        if (Auth::user()->role !== 'gerente') abort(403);
        $v = $request->validate([
            'stock_item_id' => 'required|exists:stock_items,id',
            'quantidade'    => 'required|numeric|min:0.001|max:99999',
            'motivo'        => 'nullable|string|max:255',
        ]);
        $item = StockItem::find($v['stock_item_id']);
        $anterior = $item->quantidade_atual;
        $item->quantidade_atual += $v['quantidade'];
        $item->save();
        StockMovement::create([
            'stock_item_id'      => $item->id,
            'user_id'            => Auth::id(),
            'tipo'               => 'entrada',
            'quantidade'         => $v['quantidade'],
            'quantidade_anterior'=> $anterior,
            'quantidade_nova'    => $item->quantidade_atual,
            'motivo'             => $v['motivo'] ?? 'Entrada manual',
        ]);
        return back()->with('success', "✅ Entrada de {$v['quantidade']} {$item->unidade} registrada para {$item->nome}!");
    }

    public function saida(Request $request)
    {
        if (Auth::user()->role !== 'gerente') abort(403);
        $v = $request->validate([
            'stock_item_id' => 'required|exists:stock_items,id',
            'quantidade'    => 'required|numeric|min:0.001|max:99999',
            'motivo'        => 'nullable|string|max:255',
        ]);
        $item = StockItem::find($v['stock_item_id']);
        if ($item->quantidade_atual < $v['quantidade']) {
            return back()->withInput()->with('error', "❌ Estoque insuficiente. Disponível: {$item->quantidade_atual} {$item->unidade}.");
        }
        $anterior = $item->quantidade_atual;
        $item->quantidade_atual -= $v['quantidade'];
        $item->save();
        StockMovement::create([
            'stock_item_id'      => $item->id,
            'user_id'            => Auth::id(),
            'tipo'               => 'saida',
            'quantidade'         => $v['quantidade'],
            'quantidade_anterior'=> $anterior,
            'quantidade_nova'    => $item->quantidade_atual,
            'motivo'             => $v['motivo'] ?? 'Saída manual',
        ]);
        return back()->with('success', "✅ Saída de {$v['quantidade']} {$item->unidade} registrada para {$item->nome}!");
    }
}
