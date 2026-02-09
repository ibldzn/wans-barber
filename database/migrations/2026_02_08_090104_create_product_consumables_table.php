<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('consumable_product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('qty_per_unit')->default(1);
            $table->timestamps();

            $table->unique(['service_product_id', 'consumable_product_id'], 'unique_service_consumable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_consumables');
    }
};
