<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('unidade')->default('un');
            $table->string('unidade_original')->nullable();
            $table->boolean('usa_gramas')->default(false);
            $table->decimal('quantidade_atual', 15, 3)->default(0);
            $table->decimal('quantidade_minima', 15, 3)->default(0);
            $table->decimal('preco_unitario', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
