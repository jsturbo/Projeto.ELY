<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Table;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        if (!Auth::user() || Auth::user()->role !== 'garcom') {
            abort(403, 'Acesso negado. Apenas usuários com papel de garçom podem criar pedidos.');
        }
        $tableId = $request->query('table_id');

        // Bloquear se a conta da mesa já foi fechada
        if ($tableId) {
            $contaFechada = Order::where('table_id', $tableId)
                ->where('status', 'pronto_entrega')
                ->exists();

            if ($contaFechada) {
                return redirect()->route('mesas.conta', $tableId)
                    ->with('error', '❌ A conta desta mesa já foi fechada. Não é possível adicionar novos pedidos.');
            }
        }
        // Carrega stockItem junto para o filtro funcionar corretamente
        $categorias = Category::with('menuItems.stockItem')->get();

        // Filtrar itens indisponíveis por falta de estoque
        $categoriasDisponiveis = $categorias->map(function($categoria) {
            $categoria->menuItems = $categoria->menuItems->filter(function($item) {
                if (!$item->disponivel) return false;
                // Se não tem ingrediente vinculado, sempre disponível
                if (!$item->stockItem) return true;
                // Se tem ingrediente, checar se há estoque
                return $item->stockItem->quantidade_atual > 0;
            });
            return $categoria;
        })->filter(fn($cat) => $cat->menuItems->count() > 0);

        $mesas = Table::all();

        return view('orders.create', [
            'tableId' => $tableId,
            'categorias' => $categoriasDisponiveis,
            'mesas' => $mesas,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user() || Auth::user()->role !== 'garcom') {
            abort(403, 'Acesso negado. Apenas usuários com papel de garçom podem criar pedidos.');
        }
        $validated = $request->validate([
            'table_id'             => 'required|exists:tables,id',
            'observacoes'          => 'nullable|string',
            'itens'                => 'required|array',
            'itens.*.menu_item_id' => 'required|exists:menu_items,id',
            'itens.*.quantidade'   => 'required|integer|min:1',
        ]);

        // Bloquear se a conta da mesa já foi fechada
        $contaFechada = Order::where('table_id', $validated['table_id'])
            ->where('status', 'pronto_entrega')
            ->exists();

        if ($contaFechada) {
            return redirect()->route('mesas.conta', $validated['table_id'])
                ->with('error', '❌ A conta desta mesa já foi fechada. Não é possível adicionar novos pedidos.');
        }

        // Validação extra (servidor): agregar quantidades por stock_item e checar disponibilidade
        $requiredByStock = [];
        $menuItemsCache = [];

        foreach ($validated['itens'] as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            if (!$menuItem) {
                return back()->with('error', "Item de menu não encontrado (ID: {$item['menu_item_id']}).");
            }
            $menuItemsCache[$menuItem->id] = $menuItem;
            if ($menuItem->stockItem) {
                $stockId = $menuItem->stockItem->id;
                if (!isset($requiredByStock[$stockId])) $requiredByStock[$stockId] = 0;
                $requiredByStock[$stockId] += $item['quantidade'];
            }
        }

        $insuficientes = [];
        foreach ($requiredByStock as $stockId => $requiredQty) {
            $stockItem = StockItem::find($stockId);
            if (!$stockItem || $stockItem->quantidade_atual < $requiredQty) {
                $insuficientes[] = [
                    'stock' => $stockItem,
                    'required' => $requiredQty,
                ];
            }
        }

        if (count($insuficientes) > 0) {
            $messages = [];
            foreach ($insuficientes as $inc) {
                $nome = $inc['stock'] ? $inc['stock']->nome : 'Item de estoque removido';
                $available = $inc['stock'] ? $inc['stock']->quantidade_atual : 0;
                $messages[] = "{$nome} - disponível: {$available}, necessário: {$inc['required']}";
            }
            return back()->with('error', 'Estoque insuficiente: ' . implode('; ', $messages));
        }

        // Criar pedido dentro de transação para consistência
        $pedido = DB::transaction(function () use ($validated, $menuItemsCache) {
            $total = 0;
            $pedido = Order::create([
                'table_id' => $validated['table_id'],
                'user_id' => Auth::id(),
                'status' => 'em_preparo',
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            // Marcar mesa como ocupada
            Table::find($validated['table_id'])?->update(['status' => 'ocupada']);

            foreach ($validated['itens'] as $item) {
                $menuItem = $menuItemsCache[$item['menu_item_id']];
                $subtotal = $menuItem->preco * $item['quantidade'];

                OrderItem::create([
                    'order_id'       => $pedido->id,
                    'menu_item_id'   => $menuItem->id,
                    'quantidade'     => $item['quantidade'],
                    'preco_unitario' => $menuItem->preco,
                    'subtotal'       => $subtotal,
                ]);

                $total += $subtotal;

            }

            $pedido->update(['total' => $total]);

            return $pedido;
        });

        return redirect()->route('dashboard')
            ->with('success', 'Pedido criado com sucesso!');
    }

    public function show(Order $order)
    {
        return view('orders.show', [
            'pedido' => $order->load('table', 'items', 'payment'),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if (!Auth::user() || Auth::user()->role !== 'garcom') {
            abort(403, 'Acesso negado. Apenas garçom pode atualizar status de pedidos.');
        }
        $request->validate(['status' => 'required|in:aberto,em_preparo,pronto,pronto_entrega,entregue,pago,cancelado']);

        $order->update(['status' => $request->status]);

        if ($request->status === 'entregue') {
            $order->update(['horario_entrega' => now()]);
        }

        return redirect()->route('dashboard')->with('success', 'Status atualizado!');
    }

    public function cancelar(Order $order)
    {
        if (!in_array(Auth::user()->role, ['garcom','gerente'])) {
            abort(403);
        }

        // Não pode cancelar se já foi pago
        if ($order->status === 'pago') {
            return back()->with('error', '❌ Não é possível cancelar um pedido já pago.');
        }

        // Não pode cancelar se a conta já foi fechada (pronto_entrega)
        if ($order->status === 'pronto_entrega') {
            return back()->with('error', '❌ A conta já foi fechada. Fale com o caixa para cancelar.');
        }

        $order->update(['status' => 'cancelado']);

        // Verificar se a mesa ficou sem pedidos ativos — liberar mesa
        $pedidosAtivos = Order::where('table_id', $order->table_id)
            ->whereNotIn('status', ['pago','cancelado'])
            ->count();

        if ($pedidosAtivos === 0) {
            $order->table?->update(['status' => 'disponivel']);
        }

        // Volta para a conta da mesa se vier de lá, senão para o dashboard
        if ($order->table_id) {
            return redirect()->route('mesas.conta', $order->table_id)
                ->with('success', '✅ Pedido #' . str_pad($order->id,4,'0',STR_PAD_LEFT) . ' cancelado.');
        }

        return redirect()->route('dashboard')
            ->with('success', '✅ Pedido cancelado.');
    }
}
