<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantidade', 10, 3);
            $table->decimal('preco_unitario', 12, 2);
            $table->decimal('total', 15, 2);
            $table->string('fornecedor')->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('status', ['pendente', 'recebido', 'cancelado'])->default('recebido');
            $table->date('data_entrega')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};
