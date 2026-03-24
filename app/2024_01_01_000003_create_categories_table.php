<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ChefController;
use App\Http\Controllers\CaixaController;
use App\Http\Controllers\GerenciarController;
use App\Http\Controllers\GestaoRelatoriosController;
use App\Http\Controllers\ControleEstoqueController;

use App\Http\Controllers\UserController;

Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::get('/login/usuarios', [LoginController::class, 'usuariosPorCargo'])->name('login.usuarios');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/usuarios',                    [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/novo',               [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios',                   [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar',   [UserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}',          [UserController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/toggle', [UserController::class, 'toggleAtivo'])->name('usuarios.toggle');
    Route::delete('/usuarios/{usuario}',       [UserController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/vendas', [DashboardController::class, 'vendas'])->name('dashboard.vendas');
    Route::get('/dashboard/mesas', [DashboardController::class, 'mesas'])->name('dashboard.mesas');
    Route::get('/dashboard/pedidos', [DashboardController::class, 'pedidos'])->name('dashboard.pedidos');
    Route::get('/dashboard/relatorios', [DashboardController::class, 'relatorios'])->name('dashboard.relatorios');

    Route::get('/pedidos/novo', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/pedidos/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/pedidos/{order}/cancelar', [OrderController::class, 'cancelar'])->name('orders.cancelar');

    Route::get('/mesas',                        [TableController::class, 'index'])->name('mesas.index');
    Route::get('/mesas/criar',                  [TableController::class, 'create'])->name('mesas.create');
    Route::post('/mesas',                       [TableController::class, 'store'])->name('mesas.store');
    Route::get('/mesas/{mesa}/conta',           [TableController::class, 'conta'])->name('mesas.conta');
    Route::post('/mesas/{mesa}/fechar-conta',   [TableController::class, 'fecharConta'])->name('mesas.fechar-conta');
    Route::post('/mesas/{mesa}/pagar-conta',    [TableController::class, 'pagarConta'])->name('mesas.pagar-conta');
    Route::get('/mesas/{table}',                [TableController::class, 'show'])->name('mesas.show');
    Route::get('/mesas/{table}/editar',         [TableController::class, 'edit'])->name('mesas.edit');
    Route::put('/mesas/{table}',                [TableController::class, 'update'])->name('mesas.update');
    Route::delete('/mesas/{table}',             [TableController::class, 'destroy'])->name('mesas.destroy');
    Route::patch('/mesas/{mesa}/atualizar',    [TableController::class, 'atualizar'])->name('mesas.atualizar');

    Route::get('/estoque', [StockController::class, 'index'])->name('dashboard.estoque');
    Route::post('/estoque/{item}/movimento', [StockController::class, 'registrarMovimento'])->name('estoque.movimento');

    Route::get('/compras', [PurchaseController::class, 'index'])->name('compras.index');
    Route::post('/compras', [PurchaseController::class, 'store'])->name('compras.store');
    Route::patch('/compras/{purchase}/cancelar', [PurchaseController::class, 'cancelar'])->name('compras.cancelar');

    Route::get('/chef/preparo', [ChefController::class, 'preparo'])->name('chef.preparo');
    Route::get('/chef/estoque', [ChefController::class, 'estoque'])->name('chef.estoque');
    Route::patch('/chef/item/{item}/status', [ChefController::class, 'marcarItemComo'])->name('chef.item.status');


    // ── Gerenciamento (Gerente) ─────────────────────────────
    Route::get('/gerenciar',               fn() => redirect()->route('gerenciar.mesas'))->name('gerenciar');
    Route::get('/gerenciar/mesas',         [GerenciarController::class, 'mesas'])->name('gerenciar.mesas');
    Route::get('/gerenciar/cardapio',      [GerenciarController::class, 'cardapio'])->name('gerenciar.cardapio');
    Route::post('/gerenciar/cardapio',     [GerenciarController::class, 'cardapioStore'])->name('gerenciar.cardapio.store');
    Route::put('/gerenciar/cardapio/{item}', [GerenciarController::class, 'cardapioUpdate'])->name('gerenciar.cardapio.update');
    Route::delete('/gerenciar/cardapio/{item}', [GerenciarController::class, 'cardapioDestroy'])->name('gerenciar.cardapio.destroy');
    Route::get('/gerenciar/funcionarios',  [GerenciarController::class, 'funcionarios'])->name('gerenciar.funcionarios');
    Route::get('/gerenciar/produtos',      [GerenciarController::class, 'produtos'])->name('gerenciar.produtos');
    Route::post('/gerenciar/produtos',     [GerenciarController::class, 'produtosStore'])->name('gerenciar.produtos.store');
    Route::put('/gerenciar/produtos/{item}', [GerenciarController::class, 'produtosUpdate'])->name('gerenciar.produtos.update');
    Route::delete('/gerenciar/produtos/{item}', [GerenciarController::class, 'produtosDestroy'])->name('gerenciar.produtos.destroy');

    // ── Relatórios de Gestão ────────────────────────────────
    Route::get('/gestao/relatorios',       [GestaoRelatoriosController::class, 'index'])->name('gestao.relatorios');
    Route::get('/gestao/relatorios/pdf',   [GestaoRelatoriosController::class, 'pdf'])->name('gestao.relatorios.pdf');

    // ── Controle de Estoque ─────────────────────────────────
    Route::get('/controle-estoque',        [ControleEstoqueController::class, 'index'])->name('controle.estoque');
    Route::post('/controle-estoque/entrada', [ControleEstoqueController::class, 'entrada'])->name('controle.estoque.entrada');
    Route::post('/controle-estoque/saida',   [ControleEstoqueController::class, 'saida'])->name('controle.estoque.saida');

    Route::get('/caixa', [CaixaController::class, 'dashboard'])->name('caixa.dashboard');
    Route::get('/caixa/pagar-mesa', [CaixaController::class, 'pagarMesa'])->name('caixa.pagar-mesa');
    Route::post('/caixa/sangria', [CaixaController::class, 'registrarSangria'])->name('caixa.sangria');
    Route::post('/caixa/pagamento/{order}', [CaixaController::class, 'confirmarPagamento'])->name('caixa.pagamento');
});
