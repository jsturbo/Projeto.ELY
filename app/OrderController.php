<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index(): View
    {
        $itens = StockItem::all();
        $estoqueAlerta = $itens->filter(function($item) {
            return $item->quantidade_atual <= $item->quantidade_minima;
        });

        return view('dashboard.estoque', [
            'itens' => $itens,
            'estoqueAlerta' => $estoqueAlerta,
        ]);
    }

    public function registrarMovimento(StockItem $item): RedirectResponse
    {
        $validated = request()->validate([
            'tipo' => 'required|in:entrada,saida',
            'quantidade' => 'required|numeric|min:0.01|max:99999',
            'motivo' => 'nullable|string|max:255',
        ]);

        $quantidade_anterior = $item->quantidade_atual;

        if ($validated['tipo'] === 'entrada') {
            $item->quantidade_atual += $validated['quantidade'];
        } else {
            $item->quantidade_atual = max(0, $item->quantidade_atual - $validated['quantidade']);
        }

        $item->save();

        // Registrar o movimento
        StockMovement::create([
            'stock_item_id' => $item->id,
            'tipo' => $validated['tipo'],
            'quantidade' => $validated['quantidade'],
            'quantidade_anterior' => $quantidade_anterior,
            'quantidade_nova' => $item->quantidade_atual,
            'motivo' => $validated['motivo'] ?? 'Movimento manual',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('dashboard.estoque')->with('success', 'Movimento registrado com sucesso!');
    }
}
