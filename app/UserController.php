<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sangria;
use App\Models\StockItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GestaoRelatoriosController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'gerente') abort(403);

        $di = request('data_inicio')
            ? Carbon::parse(request('data_inicio'))->startOfDay()
            : Carbon::today()->subDays(29)->startOfDay();
        $df = request('data_fim')
            ? Carbon::parse(request('data_fim'))->endOfDay()
            : Carbon::today()->endOfDay();

        // ── 1. Vendas por dia
        $vendasPorDia = Payment::whereBetween('created_at',[$di,$df])
            ->where('status','confirmado')
            ->get()
            ->groupBy(fn($p) => $p->created_at->format('d/m'))
            ->map(fn($g) => ['total'=>$g->sum('valor_final'), 'qtd'=>$g->count()])
            ->sortKeys();

        // ── 2. Desempenho por garçom
        $desempenhoGarcom = Order::with('user')
            ->whereBetween('created_at',[$di,$df])
            ->where('status','!=','cancelado')
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => [
                'nome'    => $g->first()->user->name ?? '—',
                'pedidos' => $g->count(),
                'total'   => $g->sum('total'),
            ])->sortByDesc('total');

        // ── 3. Itens mais vendidos
        $itensMaisVendidos = OrderItem::with('menuItem')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at',[$di,$df])->where('status','!=','cancelado'))
            ->get()
            ->groupBy('menu_item_id')
            ->map(fn($g) => [
                'nome'     => $g->first()->menuItem->nome ?? '—',
                'qtd'      => $g->sum('quantidade'),
                'receita'  => $g->sum('subtotal'),
            ])->sortByDesc('qtd')->take(15);

        // ── 4. Tempo médio de preparo (em minutos)
        $tempoPreparo = Order::whereBetween('created_at',[$di,$df])
            ->whereNotNull('horario_pronto')
            ->whereNotNull('horario_pedido')
            ->get()
            ->map(fn($o) => $o->horario_pedido->diffInMinutes($o->horario_pronto));
        $tempoMedio = $tempoPreparo->count() > 0 ? round($tempoPreparo->avg(), 1) : 0;
        $tempoMax   = $tempoPreparo->count() > 0 ? round($tempoPreparo->max(), 1) : 0;
        $tempoMin   = $tempoPreparo->count() > 0 ? round($tempoPreparo->min(), 1) : 0;

        // ── 5. Custo de insumos (compras)
        $custoInsumos = Purchase::whereBetween('created_at',[$di,$df])
            ->where('status','recebido')
            ->with('stockItem')
            ->get()
            ->groupBy('stock_item_id')
            ->map(fn($g) => [
                'nome'  => $g->first()->stockItem->nome ?? '—',
                'total' => $g->sum('total'),
                'qtd'   => $g->sum('quantidade'),
            ])->sortByDesc('total')->take(10);

        // ── 6. Desperdício (itens cancelados)
        $cancelamentos = Order::whereBetween('created_at',[$di,$df])
            ->where('status','cancelado')
            ->with('items.menuItem')
            ->get();
        $itensCancelados = $cancelamentos->flatMap(fn($o) => $o->items)
            ->groupBy('menu_item_id')
            ->map(fn($g) => [
                'nome'   => $g->first()->menuItem->nome ?? '—',
                'qtd'    => $g->sum('quantidade'),
                'valor'  => $g->sum('subtotal'),
            ])->sortByDesc('valor')->take(10);

        // ── 7. Picos de venda por hora
        $picosPorHora = Payment::whereBetween('created_at',[$di,$df])
            ->where('status','confirmado')
            ->get()
            ->groupBy(fn($p) => $p->created_at->format('H') . 'h')
            ->map(fn($g) => ['total'=>$g->sum('valor_final'), 'qtd'=>$g->count()])
            ->sortKeys();

        // ── 8. Ticket médio
        $pagamentos    = Payment::whereBetween('created_at',[$di,$df])->where('status','confirmado')->get();
        $totalVendas   = $pagamentos->sum('valor_final');
        $ticketMedio   = $pagamentos->count() > 0 ? $totalVendas / $pagamentos->count() : 0;

        // ── 9. Cancelamentos
        $totalPedidos     = Order::whereBetween('created_at',[$di,$df])->count();
        $totalCancelados  = Order::whereBetween('created_at',[$di,$df])->where('status','cancelado')->count();
        $taxaCancelamento = $totalPedidos > 0 ? round($totalCancelados / $totalPedidos * 100, 1) : 0;

        // ── 10. Estoque ABC (classificação por valor)
        $estoqueABC = StockItem::all()->map(fn($s) => [
            'nome'   => $s->nome,
            'valor'  => $s->quantidade_atual * $s->preco_unitario,
            'qtd'    => $s->quantidade_atual,
            'unidade'=> $s->unidade,
            'status' => $s->quantidade_atual <= 0 ? 'esgotado' : ($s->quantidade_atual <= $s->quantidade_minima ? 'critico' : 'ok'),
        ])->sortByDesc('valor');
        $totalValorEstoque = $estoqueABC->sum('valor');
        $acumulado = 0;
        $estoqueABC = $estoqueABC->map(function($item) use ($totalValorEstoque, &$acumulado) {
            $acumulado += $item['valor'];
            $pct = $totalValorEstoque > 0 ? ($acumulado / $totalValorEstoque) * 100 : 0;
            $item['classe'] = $pct <= 80 ? 'A' : ($pct <= 95 ? 'B' : 'C');
            $item['pct_valor'] = $totalValorEstoque > 0 ? round($item['valor'] / $totalValorEstoque * 100, 1) : 0;
            return $item;
        });

        // ── 11. Satisfação (pedidos entregues sem cancelamento / total)
        $pedidosEntregues = Order::whereBetween('created_at',[$di,$df])->where('status','pago')->count();
        $satisfacao = $totalPedidos > 0 ? round(($pedidosEntregues / $totalPedidos) * 100, 1) : 0;

        // ── 12. Previsão ML simples (média móvel 7 dias)
        $ultimos14 = collect();
        for ($i = 13; $i >= 0; $i--) {
            $dia = Carbon::today()->subDays($i);
            $valor = Payment::whereDate('created_at', $dia)->where('status','confirmado')->sum('valor_final');
            $ultimos14->push(['dia' => $dia->format('d/m'), 'valor' => $valor]);
        }
        $media7 = $ultimos14->take(-7)->avg('valor');
        $previsaoAmanha = round($media7 * 1.05, 2); // +5% tendência
        $previsaoSemana = round($media7 * 7, 2);

        // ── Totais para KPIs
        $totalCompras  = Purchase::whereBetween('created_at',[$di,$df])->where('status','recebido')->sum('total');
        $totalSangrias = Sangria::whereBetween('created_at',[$di,$df])->sum('valor');
        $lucro         = $totalVendas - $totalCompras - $totalSangrias;

        return view('gestao.relatorios', compact(
            'di','df','vendasPorDia','desempenhoGarcom','itensMaisVendidos',
            'tempoMedio','tempoMax','tempoMin','custoInsumos','itensCancelados',
            'picosPorHora','totalVendas','ticketMedio','totalPedidos',
            'totalCancelados','taxaCancelamento','estoqueABC','totalValorEstoque',
            'satisfacao','ultimos14','previsaoAmanha','previsaoSemana',
            'totalCompras','totalSangrias','lucro','pedidosEntregues'
        ));
    }

    public function pdf()
    {
        if (Auth::user()->role !== 'gerente') abort(403);

        // Usa os mesmos dados do index
        $inicio = request('inicio') ? \Carbon\Carbon::parse(request('inicio'))->startOfDay() : \Carbon\Carbon::today()->subDays(29)->startOfDay();
        $fim    = request('fim')    ? \Carbon\Carbon::parse(request('fim'))->endOfDay()       : \Carbon\Carbon::today()->endOfDay();

        $pagamentos = Payment::whereBetween('created_at', [$inicio, $fim])->where('status','confirmado')->get();
        $totalVendas  = $pagamentos->sum('valor_final');
        $totalPedidos = Order::whereBetween('created_at', [$inicio, $fim])->count();
        $ticketMedio  = $pagamentos->count() > 0 ? round($totalVendas / $pagamentos->count(), 2) : 0;
        $cancelamentos = Order::whereBetween('created_at', [$inicio, $fim])->where('status','cancelado')->count();
        $taxaCancelamento = $totalPedidos > 0 ? round(($cancelamentos / $totalPedidos) * 100, 1) : 0;
        $custoInsumos = Purchase::whereBetween('created_at', [$inicio, $fim])->where('status','recebido')->sum('total');
        $margemBruta  = $totalVendas > 0 ? round((($totalVendas - $custoInsumos) / $totalVendas) * 100, 1) : 0;
        $totalSangrias = Sangria::whereBetween('created_at', [$inicio, $fim])->sum('valor');
        $lucro = $totalVendas - $custoInsumos - $totalSangrias;

        $itensMaisVendidos = OrderItem::with('menuItem')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$inicio, $fim])->whereNotIn('status',['cancelado']))
            ->get()->groupBy('menu_item_id')
            ->map(fn($g) => ['nome'=>$g->first()->menuItem->nome??'—','quantidade'=>$g->sum('quantidade'),'receita'=>round($g->sum('subtotal'),2)])
            ->sortByDesc('quantidade')->take(10)->values();

        $desempenhoGarcom = Order::whereBetween('created_at', [$inicio, $fim])->whereNotIn('status',['cancelado'])
            ->with('user')->get()->groupBy('user_id')
            ->map(fn($g) => ['nome'=>$g->first()->user->name??'—','pedidos'=>$g->count(),'total'=>round($g->sum('total'),2)])
            ->sortByDesc('total')->values();

        $vendasDia = $pagamentos->groupBy(fn($p) => $p->created_at->format('d/m'))
            ->map(fn($g) => round($g->sum('valor_final'),2))->sortKeys();

        $porMetodo = $pagamentos->groupBy('metodo')
            ->map(fn($g) => ['qtd'=>$g->count(),'total'=>round($g->sum('valor_final'),2)]);

        $estoqueAlerta = StockItem::whereColumn('quantidade_atual','<=','quantidade_minima')->orderBy('quantidade_atual')->get();

        return view('gestao.relatorios-pdf', compact(
            'inicio','fim','totalVendas','totalPedidos','ticketMedio',
            'cancelamentos','taxaCancelamento','custoInsumos','margemBruta',
            'totalSangrias','lucro','itensMaisVendidos','desempenhoGarcom',
            'vendasDia','porMetodo','estoqueAlerta'
        ));
    }

}