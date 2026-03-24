<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\MenuItemIngredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ChefController extends Controller
{
    public function dashboard(): View
    {
        return $this->preparo();
    }

    public function preparo(): View
    {
        if (!Auth::user() || Auth::user()->role !== 'chef') {
            abort(403, 'Acesso negado. Área do chef apenas para usuários com papel de chef.');
        }
        // Pedidos em preparo com seus items
        $pedidosEmPreparo = Order::where('status', 'em_preparo')
            ->with('table', 'user', 'items.menuItem.ingredients.stockItem', 'items.menuItem.stockItem')
            ->orderBy('horario_pedido')
            ->get();

        // Contar items prontos vs ainda preparando
        $totalItems = 0;
        $itensProntos = 0;

        foreach ($pedidosEmPreparo as $pedido) {
            foreach ($pedido->items as $item) {
                $totalItems++;
                if ($item->status === 'pronto') {
                    $itensProntos++;
                }
            }
        }

        return view('dashboard.chef-preparo', [
            'pedidosEmPreparo' => $pedidosEmPreparo,
            'totalItems' => $totalItems,
            'itensProntos' => $itensProntos,
        ]);
    }

    public function estoque(): View
    {
        if (!Auth::user() || Auth::user()->role !== 'chef') {
            abort(403, 'Acesso negado. Área do chef apenas para usuários com papel de chef.');
        }
        $itens = StockItem::with('menuItems')->get();

        return view('dashboard.chef-estoque', [
            'itens' => $itens,
        ]);
    }

    public function marcarItemComo(OrderItem $item): RedirectResponse
    {
        if (!Auth::user() || Auth::user()->role !== 'chef') {
            abort(403, 'Acesso negado. Apenas chef pode marcar itens de preparo.');
        }
        $status = request('status');

        if (!in_array($status, ['pendente', 'em_preparo', 'pronto', 'entregue'])) {
            return back()->with('error', 'Status inválido');
        }

        $item->status = $status;

        if ($status === 'pronto') {
            $item->horario_pronto = now();

            // ── Descontar estoque ao marcar como pronto ──────
            $item->load('menuItem.ingredients.stockItem', 'menuItem.stockItem');
            $menuItem = $item->menuItem;

            if ($menuItem) {
                $unidadesPeso = ['kg','g','gramas','grama','l','ml'];

                if ($menuItem->ingredients->isNotEmpty()) {
                    // Ingredientes detalhados
                    foreach ($menuItem->ingredients as $ing) {
                        $stock = $ing->stockItem;
                        if (!$stock) continue;

                        $qtdPorcao = 0;
                        if (!empty($ing->quantidade) && $ing->quantidade > 0) {
                            $qtdPorcao = (float) $ing->quantidade;
                        } elseif (!empty($ing->quantidade_gramas) && $ing->quantidade_gramas > 0) {
                            $qtdPorcao = strtolower($stock->unidade) === 'kg'
                                ? $ing->quantidade_gramas / 1000
                                : $ing->quantidade_gramas;
                        }
                        if ($qtdPorcao <= 0) continue;

                        $qtdDescontar = $qtdPorcao * $item->quantidade;
                        $anterior = $stock->quantidade_atual;
                        $stock->quantidade_atual = max(0, $stock->quantidade_atual - $qtdDescontar);
                        $stock->save();

                        StockMovement::create([
                            'stock_item_id'       => $stock->id,
                            'user_id'             => Auth::id(),
                            'tipo'                => 'saida',
                            'quantidade'          => $qtdDescontar,
                            'quantidade_anterior' => $anterior,
                            'quantidade_nova'     => $stock->quantidade_atual,
                            'motivo'              => "Pedido #{$item->order_id} — {$menuItem->nome} ×{$item->quantidade} (chef marcou pronto)",
                        ]);
                    }
                } elseif ($menuItem->stockItem) {
                    // Ingrediente direto
                    $stock   = $menuItem->stockItem;
                    $unidade = strtolower($stock->unidade);
                    $qtdPorcao    = in_array($unidade, $unidadesPeso) ? 0.3 : 1;
                    $qtdDescontar = $qtdPorcao * $item->quantidade;
                    $anterior = $stock->quantidade_atual;
                    $stock->quantidade_atual = max(0, $stock->quantidade_atual - $qtdDescontar);
                    $stock->save();

                    StockMovement::create([
                        'stock_item_id'       => $stock->id,
                        'user_id'             => Auth::id(),
                        'tipo'                => 'saida',
                        'quantidade'          => $qtdDescontar,
                        'quantidade_anterior' => $anterior,
                        'quantidade_nova'     => $stock->quantidade_atual,
                        'motivo'              => "Pedido #{$item->order_id} — {$menuItem->nome} ×{$item->quantidade} (chef marcou pronto)",
                    ]);
                }
            }
        }

        $item->save();

        // Verificar se todos os items do pedido estão prontos
        // Recarregar a relação para evitar cache desatualizado
        $pedido = $item->order;
        $pedido->load('items');
        $todosRontos = $pedido->items->every(fn($it) => $it->status === 'pronto');

        if ($todosRontos && $pedido->status === 'em_preparo') {
            $pedido->status = 'pronto_entrega';
            $pedido->horario_termino_preparo = now();
            $pedido->save();
        }

        $labelStatus = match($status) {
            'pendente' => 'Pendente',
            'em_preparo' => 'Preparando',
            'pronto' => 'Pronto',
            'entregue' => 'Entregue',
            default => $status
        };

        return redirect()->route('chef.preparo')->with('success', "Item marcado como " . $labelStatus);
    }
}
