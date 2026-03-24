# 🏗️ Arquitetura do Sistema

## 📐 Diagrama de Conceito

```
┌─────────────────────────────────────────────────────────────┐
│              DASHBOARD RESTAURANTE                          │
└─────────────────────────────────────────────────────────────┘
                          ▼
┌────────────────────────────────────────────────────────────┐
│                    AUTENTICAÇÃO LARAVEL                   │
│              (Email + Senha + Role)                      │
└────────────────────────────────────────────────────────────┘
                          ▼
        ┌─────────────────┬──────────────┬──────────────┐
        ▼                 ▼              ▼              ▼
    ┌────────┐      ┌────────┐     ┌────────┐    ┌─────────┐
    │  ADMIN │      │GERENTE │     │GARÇOM  │    │ CHEF    │
    └────────┘      └────────┘     └────────┘    └─────────┘
        ▼                 ▼              ▼              ▼
  Dashboard Completo  Vendas+Mesas  Mesas+Pedidos  Pedidos Prep.
  Todos os Moduls    Estoque+Report Cardápio      Fila Cozinha

        └─────────────────────────────────────────────────────┘
```

## 🗄️ Banco de Dados - Relacionamentos

```
users (1)
  ├─ (1:N) ─→ orders (pedidos do garçom)
  ├─ (1:N) ─→ tables (mesas atribuidas)
  └─ (1:N) ─→ stock_movements (movimentos)

tables (1)
  ├─ (1:N) ─→ orders
  └─ (N:1) ─→ users (garcom_id - nullable)

categories (1)
  └─ (1:N) ─→ menu_items

menu_items (1)
  └─ (1:N) ─→ order_items

orders (1)
  ├─ (1:N) ─→ order_items
  └─ (1:N) ─→ payments
  
order_items (N:1)
  ├─→ orders
  └─→ menu_items

payments (N:1)
  └─→ orders

stock_items (1)
  └─ (1:N) ─→ stock_movements

stock_movements (N:1)
  ├─→ stock_items
  └─→ users
```

## 🔄 Fluxo de Pedido

```
┌──────────────────┐
│ Mesa Disponível  │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Garçom Cria       │───→ POST /pedidos
│ Novo Pedido      │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Pedido Status:    │
│ ABERTO           │───→ orders.status = 'aberto'
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Chef Visualiza   │───→ Notificação/Dashboard
│ Pedido na Cozinha│
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Pedido em Preparo│───→ orders.status = 'em_preparo'
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Items Prontos    │───→ order_items.status = 'pronto'
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Garçom Busca     │
│ Items            │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Pedido Entregue  │───→ orders.status = 'entregue'
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Caixa Registra   │───→ payments.status = 'confirmado'
│ Pagamento        │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Mesa Liberada    │───→ tables.status = 'disponivel'
└──────────────────┘
```

## 🎯 Estados de Pedido

```
Para Pedidos:
┌─────────────┐    ┌──────────┐    ┌───────┐    ┌──────────┐
│   ABERTO    │───▶│ Em Prep. │───▶│ PRONTO│───▶│ ENTREGUE │
└─────────────┘    └──────────┘    └───────┘    └──────────┘
    │
    ▼
┌──────────────┐
│ CANCELADO    │
└──────────────┘

Para Items:
┌──────────┐    ┌──────────┐    ┌───────┐    ┌──────────┐
│ PENDENTE │───▶│ Em Prep. │───▶│ PRONTO│───▶│ ENTREGUE │
└──────────┘    └──────────┘    └───────┘    └──────────┘
```

## 📊 Estrutura de Vendas

```
payment (N:1) → orders

payment:
├─ valor (preço base)
├─ taxa (se cartão, por ex.)
├─ valor_final (valor + taxa)
├─ metodo [dinheiro, cartao_credito, cartao_debito, pix]
└─ status [pendente, confirmado, falhou]
```

## 🔐 Controle de Acesso

```
Middleware: auth (todas as rotas do dashboard)

Roles definidos no enum:
├─ admin      → Acesso total
├─ gerente    → Vendas + Estoque + Mesas
├─ garcom     → Mesas + Pedidos
├─ chef       → Visualizar pedidos
└─ caixa      → Vendas + Pagamentos
```

## 🗂️ Controllers e Seus Métodos

```
DashboardController
├─ index()            → Dashboard principal (com role check)
├─ vendas()           → Relatório de vendas
├─ mesas()            → Gerenciamento de mesas
├─ pedidos()          → Lista de pedidos
├─ estoque()          → Gerenciamento de estoque
└─ relatorios()       → Relatórios customizáveis

TableController
├─ index()            → Listar mesas
├─ show()             → Detalhes da mesa + pedido ativo
└─ atualizar()        → Mudar status

OrderController
├─ create()           → Formulário novo pedido
├─ store()            → Salvar pedido
├─ show()             → Detalhes pedido
└─ updateStatus()     → Atualizar status
```

## 🎨 Camadas da Aplicação

```
┌─────────────────────────────────────┐
│     VIEWS (Blade Templates)         │  ← Apresentação
├─────────────────────────────────────┤
│     CONTROLLERS                      │  ← Lógica de Negócio
├─────────────────────────────────────┤
│     MODELS (Eloquent ORM)           │  ← Abstração de Dados
├─────────────────────────────────────┤
│     DATABASE (MySQL/SQLite)         │  ← Persistência
└─────────────────────────────────────┘
```

## 📱 Responsividade

```
Desktop (> 768px)
├─ Sidebar fixo
├─ Layout 2+ colunas
└─ Todas as funcionalidades visíveis

Mobile (< 768px)
├─ Menu toggle
├─ Layout 1 coluna
└─ Adaptado para touch
```

## 🔌 Modificações Necessárias para Expandir

### Adicionar Nova Funcionalidade
1. **Migration** → Criar tabela
2. **Model** → Criar relacionamentos
3. **Controller** → Lógica de negócio
4. **Routes** → Definir endpoints
5. **Views** → Interface

### Exemplo: Sistema de Reservas
```
1. Migration: create_reservations_table
   ├─ customer_name
   ├─ customer_phone
   ├─ table_id
   ├─ data_reserva
   └─ cantidad_pessoas

2. Model: Reservation
   ├─ belongsTo(Table)
   └─ belongsToMany(User)

3. Controller: ReservationController
   ├─ create()
   ├─ store()
   ├─ show()
   └─ confirm()

4. Routes: POST/GET /reservas

5. Views: reservas/create, reservas/show
```

## 🚀 Performance

```
Otimizações Implementadas:
├─ Eager Loading (with('relations'))
├─ Query Scoping (where conditions)
├─ Indexes em foreign keys
├─ Paginação de resultados
└─ Bootstrap CSS CDN

Recomendações:
├─ Adicionar cache Redis para dashboards
├─ Criar índices em columns frequentes
├─ Implementar soft deletes
└─ Adicionar logging de ações
```

---

**Sistema bem estruturado e extensível! 🎯**
