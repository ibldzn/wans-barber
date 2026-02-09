<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('service')->after('product_description');
            $table->boolean('track_stock')->default(false)->after('product_type');
            $table->decimal('cost_price', 12, 2)->nullable()->after('track_stock');
            $table->integer('reorder_level')->nullable()->after('cost_price');
            $table->boolean('is_active')->default(true)->after('reorder_level');
            $table->decimal('commission_rate_override_regular', 5, 4)->nullable()->after('is_active');
            $table->decimal('commission_rate_override_callout', 5, 4)->nullable()->after('commission_rate_override_regular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'track_stock',
                'cost_price',
                'reorder_level',
                'is_active',
                'commission_rate_override_regular',
                'commission_rate_override_callout',
            ]);
        });
    }
};
