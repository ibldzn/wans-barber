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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('role')->default('barber')->after('emp_phone');
            $table->string('bank_account')->nullable()->after('role');
            $table->decimal('daily_wage', 12, 2)->default(0)->after('bank_account');
            $table->decimal('meal_allowance_per_day', 12, 2)->default(0)->after('daily_wage');
            $table->decimal('commission_rate_override_regular', 5, 4)->nullable()->after('meal_allowance_per_day');
            $table->decimal('commission_rate_override_callout', 5, 4)->nullable()->after('commission_rate_override_regular');
            $table->boolean('is_active')->default(true)->after('commission_rate_override_callout');
            $table->foreignId('user_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'role',
                'bank_account',
                'daily_wage',
                'meal_allowance_per_day',
                'commission_rate_override_regular',
                'commission_rate_override_callout',
                'is_active',
                'user_id',
            ]);
        });
    }
};
