<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['aberto', 'em_preparo', 'pronto', 'pronto_entrega', 'entregue', 'pago', 'cancelado'])->default('em_preparo');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamp('horario_pedido')->nullable();
            $table->timestamp('horario_pronto')->nullable();
            $table->timestamp('horario_entrega')->nullable();
            $table->timestamp('horario_termino_preparo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
