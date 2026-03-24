<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $compras = Purchase::with('stockItem', 'user')
            ->orderByDesc('created_at')
            ->get();

        $itens = StockItem::all();

        return view('dashboard.compras', [
            'compras' => $compras,
            'itens'   => $itens,
        ]);
    }

    public function store(): RedirectResponse
    {
        if (!Auth::user() || Auth::user()->role !== 'gerente') {
            abort(403, 'Acesso negado. Apenas gerente pode registrar compras.');
        }

        // Bloqueio manual antes do validate
        $qtd        = request('quantidade');
        $preco      = request('preco_unitario');
        $fornecedor = request('fornecedor');

        if (!is_numeric($qtd) || (float)$qtd <= 0) {
            return back()->withInput()->with('error', '❌ Quantidade inválida. Informe um valor maior que zero.');
        }
        if ((float)$qtd > 99999) {
            return back()->withInput()->with('error', '❌ Quantidade muito alta! O máximo por compra é 99.999.');
        }
        if (!is_numeric($preco) || (float)$preco <= 0) {
            return back()->withInput()->with('error', '❌ Preço inválido. Informe um valor maior que zero.');
        }
        if ((float)$preco > 999999) {
            return back()->withInput()->with('error', '❌ Preço muito alto! O máximo permitido é R$ 999.999,00.');
        }
        if ($fornecedor && preg_match('/[^a-zA-ZÀ-ÿ\s\.\-]/u', $fornecedor)) {
            return back()->withInput()->with('error', '❌ Nome do fornecedor deve conter apenas letras.');
        }

        $validated = request()->validate([
            'stock_item_id'  => 'required|exists:stock_items,id',
            'quantidade'     => 'required|numeric|min:0.01|max:99999',
            'preco_unitario' => 'required|numeric|min:0.01|max:999999',
            'fornecedor'     => ['nullable', 'max:255', 'regex:/^[a-zA-ZÀ-ÿ\s\.\-]*$/u'],
            'data_entrega'   => 'nullable|date',
            'observacoes'    => 'nullable|string|max:500',
        ], [
            'quantidade.max'         => '❌ Quantidade muito alta! O máximo por compra é 99.999.',
            'quantidade.min'         => '❌ A quantidade mínima é 0,01.',
            'quantidade.required'    => '❌ Informe a quantidade.',
            'quantidade.numeric'     => '❌ A quantidade deve ser um número.',
            'preco_unitario.max'     => '❌ Preço muito alto! O máximo é R$ 999.999,00.',
            'preco_unitario.min'     => '❌ O preço deve ser maior que zero.',
            'preco_unitario.numeric' => '❌ O preço deve ser um número.',
            'fornecedor.regex'       => '❌ O nome do fornecedor deve conter apenas letras.',
        ]);

        $total = $validated['quantidade'] * $validated['preco_unitario'];

        $purchase = Purchase::create([
            'stock_item_id'  => $validated['stock_item_id'],
            'user_id'        => Auth::id(),
            'quantidade'     => $validated['quantidade'],
            'preco_unitario' => $validated['preco_unitario'],
            'total'          => $total,
            'fornecedor'     => $validated['fornecedor'] ?? null,
            'observacoes'    => $validated['observacoes'] ?? null,
            'data_entrega'   => $validated['data_entrega'] ?? null,
            'status'         => 'recebido',
        ]);

        $stockItem           = StockItem::find($validated['stock_item_id']);
        $quantidade_anterior = $stockItem->quantidade_atual;
        $stockItem->quantidade_atual += $validated['quantidade'];
        $stockItem->save();

        StockMovement::create([
            'stock_item_id'      => $stockItem->id,
            'tipo'               => 'entrada',
            'quantidade'         => $validated['quantidade'],
            'quantidade_anterior'=> $quantidade_anterior,
            'quantidade_nova'    => $stockItem->quantidade_atual,
            'motivo'             => "Compra #{$purchase->id}" . (!empty($validated['fornecedor']) ? " de {$validated['fornecedor']}" : ''),
            'user_id'            => Auth::id(),
        ]);

        return back()->with('success', '✅ Compra registrada com sucesso!');
    }

    public function cancelar(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'recebido') {
            return back()->with('error', '❌ Só é possível cancelar compras recebidas.');
        }

        $stockItem           = $purchase->stockItem;
        $quantidade_anterior = $stockItem->quantidade_atual;
        $stockItem->quantidade_atual = max(0, $stockItem->quantidade_atual - $purchase->quantidade);
        $stockItem->save();

        StockMovement::create([
            'stock_item_id'      => $stockItem->id,
            'tipo'               => 'saida',
            'quantidade'         => $purchase->quantidade,
            'quantidade_anterior'=> $quantidade_anterior,
            'quantidade_nova'    => $stockItem->quantidade_atual,
            'motivo'             => "Cancelamento da compra #{$purchase->id}",
            'user_id'            => Auth::id(),
        ]);

        $purchase->status = 'cancelado';
        $purchase->save();

        return back()->with('success', '✅ Compra cancelada e estoque revertido!');
    }
}
