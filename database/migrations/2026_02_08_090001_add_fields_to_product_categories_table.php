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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('category_type')->default('service')->after('category_description');
            $table->decimal('commission_rate_regular', 5, 4)->default(0)->after('category_type');
            $table->decimal('commission_rate_callout', 5, 4)->default(0)->after('commission_rate_regular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn([
                'category_type',
                'commission_rate_regular',
                'commission_rate_callout',
            ]);
        });
    }
};
