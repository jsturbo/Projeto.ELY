<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function index()
    {
        $mesas = Table::with(['orders' => fn($q) => $q->whereIn('status',
            ['aberto','em_preparo','pronto','pronto_entrega','entregue']
        )])->orderBy('numero')->get();

        // Sincronizar status com a realidade — pedidos sempre ganham
        foreach ($mesas as $mesa) {
            $temPedido = $mesa->orders->isNotEmpty();
            if ($temPedido && $mesa->status !== 'ocupada') {
                $mesa->update(['status' => 'ocupada']);
                $mesa->status = 'ocupada';
            } elseif (!$temPedido && $mesa->status === 'ocupada') {
                $mesa->update(['status' => 'disponivel']);
                $mesa->status = 'disponivel';
            }
        }

        return view('mesas.index', compact('mesas'));
    }

    public function show(Table $table)
    {
        $pedidoAtivo = Order::where('table_id', $table->id)
            ->whereIn('status', ['aberto', 'em_preparo'])
            ->first();

        return view('mesas.show', [
            'mesa' => $table,
            'pedidoAtivo' => $pedidoAtivo,
        ]);
    }

    // ── CONTA DA MESA ────────────────────────────────────────
    public function conta(Table $mesa)
    {
        $pedidos = Order::where('table_id', $mesa->id)
            ->whereNotIn('status', ['pago','cancelado'])
            ->with('items.menuItem','user')
            ->orderBy('created_at')
            ->get();

        $totalConta = $pedidos->sum('total');
        $totalItens = $pedidos->sum(fn($p) => $p->items->sum('quantidade'));

        // Conta está fechada se algum pedido está pronto_entrega
        $contaFechada = $pedidos->contains('status', 'pronto_entrega');

        return view('mesas.conta', compact('mesa','pedidos','totalConta','totalItens','contaFechada'));
    }

    // ── FECHAR CONTA (garçom marca como pronto p/ pagamento) ─
    public function fecharConta(Table $mesa)
    {
        if (!in_array(Auth::user()->role, ['garcom','gerente'])) abort(403);

        $pedidos = Order::where('table_id', $mesa->id)
            ->whereNotIn('status', ['pago','cancelado','pronto_entrega'])
            ->get();

        if ($pedidos->isEmpty()) {
            return back()->with('error', '❌ Nenhum pedido em aberto nesta mesa.');
        }

        foreach ($pedidos as $pedido) {
            $pedido->update(['status' => 'pronto_entrega']);
        }

        $mesa->update(['status' => 'ocupada']);

        return redirect()->route('mesas.conta', $mesa)
            ->with('success', '✅ Conta fechada! Pedidos enviados para o caixa.');
    }

    // ── PAGAR CONTA INTEIRA DA MESA (caixa/gerente) ──────────
    public function pagarConta(Request $request, Table $mesa)
    {
        if (!in_array(Auth::user()->role, ['caixa','gerente'])) abort(403);

        $request->validate([
            'metodo'    => 'required|in:dinheiro,cartao_credito,cartao_debito,pix',
            'valor_pago'=> 'required|numeric|min:0.01',
        ]);

        $pedidos = Order::where('table_id', $mesa->id)
            ->whereNotIn('status', ['pago','cancelado'])
            ->with('items')
            ->get();

        if ($pedidos->isEmpty()) {
            return back()->with('error', '❌ Nenhum pedido em aberto nesta mesa.');
        }

        $totalReal = $pedidos->sum('total');
        $valorPorPedido = $pedidos->count() > 0
            ? round($request->valor_pago / $pedidos->count(), 2)
            : $request->valor_pago;

        DB::transaction(function () use ($pedidos, $request, $totalReal, $valorPorPedido, $mesa) {
            foreach ($pedidos as $idx => $pedido) {
                // Último pedido recebe o resto para evitar diferença de arredondamento
                $valorEste = ($idx === $pedidos->count() - 1)
                    ? round($request->valor_pago - ($valorPorPedido * ($pedidos->count() - 1)), 2)
                    : $valorPorPedido;

                Payment::create([
                    'order_id'       => $pedido->id,
                    'metodo'         => $request->metodo,
                    'valor'          => $pedido->total,
                    'taxa'           => 0,
                    'valor_final'    => $valorEste,
                    'status'         => 'confirmado',
                    'data_pagamento' => now(),
                ]);

                $pedido->update(['status' => 'pago']);
            }

            $mesa->update(['status' => 'disponivel']);
        });

        return redirect()->route('mesas.index')
            ->with('success', '✅ Pagamento de R$ ' . number_format($request->valor_pago,2,',','.') . ' confirmado! Mesa liberada.');
    }

    public function create()
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') abort(403);
        return view('mesas.create');
    }

    public function store(Request $request)
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') abort(403);
        $validated = $request->validate([
            'numero'   => 'required|unique:tables|integer|min:1',
            'assentos' => 'required|integer|min:1|max:20',
        ]);
        Table::create($validated);
        return redirect()->route('mesas.index')->with('success', '✅ Mesa criada!');
    }

    public function edit(Table $table)
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') abort(403);
        return view('mesas.edit', compact('table'));
    }

    public function update(Request $request, Table $table)
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') abort(403);
        $validated = $request->validate([
            'numero'   => 'required|unique:tables,numero,'.$table->id.'|integer|min:1',
            'assentos' => 'required|integer|min:1|max:20',
        ]);
        $table->update($validated);
        return redirect()->route('mesas.index')->with('success', '✅ Mesa atualizada!');
    }

    public function destroy(Table $table)
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') abort(403);
        $table->delete();
        return redirect()->route('mesas.index')->with('success', '✅ Mesa removida!');
    }

    public function atualizar(Request $request, Table $mesa)
    {
        if (!in_array(Auth::user()->role, ['garcom','gerente'])) {
            abort(403);
        }

        $request->validate(['status' => 'required|in:disponivel,ocupada,reservada']);

        $novoStatus = $request->status;

        // Bloquear qualquer mudança manual se tiver pedidos ativos
        $pedidosAtivos = Order::where('table_id', $mesa->id)
            ->whereNotIn('status', ['pago','cancelado'])
            ->count();

        if ($pedidosAtivos > 0) {
            return back()->with('error', '❌ Mesa ' . $mesa->numero . ' tem pedidos em aberto. Finalize os pedidos para mudar o status.');
        }

        $mesa->update(['status' => $novoStatus]);

        $labels = ['disponivel' => 'Disponível', 'reservada' => 'Reservada', 'ocupada' => 'Ocupada'];
        return back()->with('success', '✅ Mesa ' . $mesa->numero . ' → ' . $labels[$novoStatus]);
    }
}
