<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('metodo', ['dinheiro', 'cartao_credito', 'cartao_debito', 'pix'])->default('dinheiro');
            $table->decimal('valor', 15, 2)->default(0);
            $table->decimal('taxa', 10, 2)->default(0);
            $table->decimal('valor_final', 15, 2)->default(0);
            $table->enum('status', ['pendente', 'confirmado', 'falhou'])->default('pendente');
            $table->timestamp('data_pagamento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
