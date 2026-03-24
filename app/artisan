<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\Sangria;
use App\Models\Purchase;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CaixaController extends Controller
{
    public function dashboard(): View
    {
        if (!Auth::user() || !in_array(Auth::user()->role, ['caixa', 'gerente'])) {
            abort(403);
        }

        $dataHoje  = Carbon::today();
        $dataInicio = $dataHoje->copy()->startOfMonth();
        $dataFim    = $dataHoje->copy()->endOfMonth();

        $vendaHoje  = Payment::whereDate('created_at', $dataHoje)->where('status','confirmado')->sum('valor_final');
        $vendaDoMes = Payment::whereBetween('created_at', [$dataInicio,$dataFim])->where('status','confirmado')->sum('valor_final');

        $pagamentosHoje = Payment::whereDate('created_at', $dataHoje)->with('order')->orderByDesc('created_at')->get();

        $pedidosProntosPagamento = Order::where('status','pronto_entrega')
            ->with('table','user','items')->orderBy('created_at')->get();

        $pagamentosCartao = Payment::whereDate('created_at', $dataHoje)
            ->whereIn('metodo',['cartao_credito','cartao_debito'])->where('status','confirmado')->sum('valor_final');
        $pagamentosNumerario = Payment::whereDate('created_at', $dataHoje)
            ->where('metodo','dinheiro')->where('status','confirmado')->sum('valor_final');

        $comprasHoje  = Purchase::whereDate('created_at', $dataHoje)->where('status','recebido')->sum('total');
        $comprasDoMes = Purchase::whereBetween('created_at', [$dataInicio,$dataFim])->where('status','recebido')->sum('total');

        $sangriasHoje  = Sangria::whereDate('created_at', $dataHoje)->sum('valor');
        $sangriasDoMes = Sangria::whereBetween('created_at', [$dataInicio,$dataFim])->sum('valor');
        $historicoSangrias = Sangria::with('user')->orderByDesc('created_at')->limit(20)->get();

        $saldoHoje = $vendaHoje - $comprasHoje - $sangriasHoje;

        return view('dashboard.caixa', compact(
            'vendaHoje','vendaDoMes','pagamentosHoje','pedidosProntosPagamento',
            'pagamentosCartao','pagamentosNumerario','comprasHoje','comprasDoMes',
            'saldoHoje','sangriasHoje','sangriasDoMes','historicoSangrias'
        ));
    }

    public function registrarSangria(): RedirectResponse
    {
        if (!Auth::user() || !in_array(Auth::user()->role, ['caixa','gerente'])) {
            abort(403);
        }
        $validated = request()->validate([
            'valor'  => 'required|numeric|min:0.01|max:999999',
            'motivo' => 'nullable|string|max:255',
        ]);

        Sangria::create([
            'user_id' => Auth::id(),
            'valor'   => $validated['valor'],
            'motivo'  => $validated['motivo'] ?? null,
        ]);

        return back()->with('success', '✅ Sangria de R$ ' . number_format($validated['valor'],2,',','.') . ' registrada!');
    }

    public function confirmarPagamento(Order $order): RedirectResponse|JsonResponse
    {
        if (!Auth::user() || !in_array(Auth::user()->role, ['caixa','gerente','garcom'])) {
            abort(403);
        }

        try {
            $validated = request()->validate([
                'metodo'     => 'required|in:dinheiro,cartao_credito,cartao_debito,pix',
                'valor_pago' => 'required|numeric|min:0.01',
            ]);
        } catch (ValidationException $e) {
            if (request()->expectsJson()) {
                return response()->json(['success'=>false,'errors'=>$e->errors()], 422);
            }
            return back()->withErrors($e)->withInput();
        }

        $order->load('items');
        $total = $order->items->sum('subtotal');

        Payment::create([
            'order_id'       => $order->id,
            'valor'          => $total,
            'valor_final'    => $validated['valor_pago'],
            'metodo'         => $validated['metodo'],
            'status'         => 'confirmado',
            'data_pagamento' => now(),
        ]);

        $order->update(['status' => 'pago']);

        if ($order->table) {
            $pedidosAtivos = Order::where('table_id', $order->table_id)
                ->whereNotIn('status',['pago','cancelado'])
                ->where('id','!=',$order->id)
                ->count();
            if ($pedidosAtivos === 0) {
                $order->table->update(['status' => 'disponivel']);
            }
        }

        if (request()->expectsJson()) {
            return response()->json(['success'=>true,'message'=>'Pagamento confirmado!']);
        }

        return redirect()->route('dashboard')->with('success', '✅ Pagamento confirmado!');
    }

    public function pagarMesa(): View
    {
        if (!Auth::user() || !in_array(Auth::user()->role, ['caixa','gerente'])) {
            abort(403);
        }

        $mesas = Table::with([
            'orders' => fn($q) => $q->whereIn('status',['aberto','em_preparo','pronto_entrega','pronto'])
        ])->get();

        return view('caixa.pagar-mesa', compact('mesas'));
    }
}
