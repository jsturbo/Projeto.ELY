<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sangria;
use App\Models\Table;
use App\Models\StockItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Dados gerais
        $pedidosAbertos = Order::where('status', 'aberto')->count();
        $mesasOcupadas = Table::where('status', 'ocupada')->count();
        $totalMesas = Table::count();

        // Cálculos financeiros
        $dataHoje = Carbon::today();
        $vendaHoje = Payment::whereDate('created_at', $dataHoje)
            ->where('status', 'confirmado')
            ->sum('valor_final');

        // Pedidos em tempo real
        $pedidosEmPreparo = Order::where('status', 'em_preparo')->with('table', 'items')->get();

        // Estoque com baixa quantidade
        $estoqueAlerta = StockItem::whereRaw('quantidade_atual <= quantidade_minima')->get();

        // Dados para rendering de dashboard por role
        return match($user->role) {
            'gerente' => view('dashboard.admin', [
                'pedidosAbertos' => $pedidosAbertos,
                'mesasOcupadas' => $mesasOcupadas,
                'totalMesas' => $totalMesas,
                'vendaHoje' => $vendaHoje,
                'pedidosEmPreparo' => $pedidosEmPreparo,
                'estoqueAlerta' => $estoqueAlerta,
            ]),
            'garcom' => view('dashboard.garcom', [
                'mesas' => Table::with(['orders' => fn($q) => $q->whereNotIn('status',['pago','cancelado'])])->orderBy('numero')->get(),
                'pedidosGarcom' => Order::where('user_id', $user->id)->with('table', 'items')->orderByDesc('created_at')->get(),
                'categorias' => Category::with('menuItems')->get(),
                'mesasOcupadas' => $mesasOcupadas,
                'totalMesas' => $totalMesas,
                'pedidosProntosPagamento' => Order::whereIn('status', ['pronto_entrega', 'pronto'])->with('table', 'items')->get(),
                'pagamentosDia' => Payment::whereDate('created_at', $dataHoje)->where('status', 'confirmado')->with('order')->orderByDesc('created_at')->get(),
                'totalPagamentosDia' => Payment::whereDate('created_at', $dataHoje)->where('status', 'confirmado')->sum('valor_final'),
            ]),
            'chef' => app(ChefController::class)->dashboard(),
            'caixa' => app(CaixaController::class)->dashboard(),
            default => view('dashboard.admin', [
                'pedidosAbertos' => $pedidosAbertos,
                'mesasOcupadas' => $mesasOcupadas,
                'totalMesas' => $totalMesas,
                'vendaHoje' => $vendaHoje,
                'pedidosEmPreparo' => $pedidosEmPreparo,
                'estoqueAlerta' => $estoqueAlerta,
            ]),
        };
    }

    public function vendas()
    {
        $dataHoje = Carbon::today();
        $dataInicio = $dataHoje->copy()->startOfMonth();
        $dataFim = $dataHoje->copy()->endOfMonth();

        $vendas = Payment::whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('status', 'confirmado')
            ->with('order.table')
            ->orderByDesc('created_at')
            ->get();

        $totalMes = $vendas->sum('valor_final');
        $totalHoje = Payment::whereDate('created_at', $dataHoje)
            ->where('status', 'confirmado')
            ->sum('valor_final');

        return view('dashboard.vendas', [
            'vendas' => $vendas,
            'totalMes' => $totalMes,
            'totalHoje' => $totalHoje,
        ]);
    }

    public function mesas()
    {
        $mesas = Table::with('garcom')->get();
        return view('dashboard.mesas', compact('mesas'));
    }

    public function pedidos()
    {
        $pedidos = Order::with('table', 'user', 'items')->orderByDesc('created_at')->get();
        return view('dashboard.pedidos', compact('pedidos'));
    }

    public function relatorios()
    {
        if (Auth::user()->role !== 'gerente') abort(403);

        $dataInicio = request('data_inicio')
            ? Carbon::parse(request('data_inicio'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();
        $dataFim = request('data_fim')
            ? Carbon::parse(request('data_fim'))->endOfDay()
            : Carbon::today()->endOfDay();

        // ── Pedidos e pagamentos no período
        $pedidos    = Order::with('user','table')
            ->whereBetween('created_at', [$dataInicio, $dataFim])->get();
        $pagamentos = Payment::with('order.table')
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('status', 'confirmado')->get();

        $totalVendas       = $pagamentos->sum('valor_final');
        $totalPedidos      = $pedidos->count();
        $pedidosCancelados = $pedidos->where('status','cancelado')->count();
        $ticketMedio       = $totalPedidos > 0 ? $totalVendas / max(1, $pagamentos->count()) : 0;

        // ── Vendas por método de pagamento
        $porMetodo = $pagamentos->groupBy('metodo')->map(fn($g) => [
            'qtd'   => $g->count(),
            'total' => $g->sum('valor_final'),
        ]);

        // ── Itens mais vendidos
        $itensMaisVendidos = OrderItem::with('menuItem')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$dataInicio, $dataFim])
                ->where('status','!=','cancelado'))
            ->get()
            ->groupBy('menu_item_id')
            ->map(fn($g) => [
                'nome'       => $g->first()->menuItem->nome ?? '—',
                'quantidade' => $g->sum('quantidade'),
                'total'      => $g->sum('subtotal'),
            ])
            ->sortByDesc('quantidade')
            ->take(10);

        // ── Vendas por dia (gráfico)
        $vendasPorDia = $pagamentos->groupBy(fn($p) => $p->created_at->format('d/m'))
            ->map(fn($g) => $g->sum('valor_final'))
            ->sortKeys();

        // ── Compras no período
        $totalCompras = Purchase::whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('status','recebido')->sum('total');

        // ── Sangrias no período
        $totalSangrias = Sangria::whereBetween('created_at', [$dataInicio, $dataFim])->sum('valor');

        // ── Lucro estimado
        $lucroEstimado = $totalVendas - $totalCompras - $totalSangrias;

        // ── Desempenho por garçom
        $porGarcom = $pedidos->where('status','!=','cancelado')
            ->groupBy('user_id')
            ->map(fn($g) => [
                'nome'    => $g->first()->user->name ?? '—',
                'pedidos' => $g->count(),
                'total'   => $g->sum('total'),
            ])
            ->sortByDesc('total')
            ->take(10);

        // ── Mesas mais usadas
        $porMesa = $pedidos->where('status','!=','cancelado')
            ->groupBy('table_id')
            ->map(fn($g) => [
                'mesa'    => 'Mesa ' . ($g->first()->table->numero ?? '?'),
                'pedidos' => $g->count(),
                'total'   => $g->sum('total'),
            ])
            ->sortByDesc('total')
            ->take(10);

        // ── Estoque crítico
        $estoqueCritico = StockItem::whereColumn('quantidade_atual','<=','quantidade_minima')
            ->orderBy('quantidade_atual')->get();

        return view('dashboard.relatorios', compact(
            'pedidos','pagamentos','totalVendas','totalPedidos',
            'pedidosCancelados','ticketMedio','porMetodo',
            'itensMaisVendidos','vendasPorDia','totalCompras',
            'totalSangrias','lucroEstimado','porGarcom','porMesa',
            'estoqueCritico','dataInicio','dataFim'
        ));
    }
}
