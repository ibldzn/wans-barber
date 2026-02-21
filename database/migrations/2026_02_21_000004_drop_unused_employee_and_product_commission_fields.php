<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $employeeColumns = [];

        foreach ([
            'daily_wage',
            'commission_rate_override_regular',
            'commission_rate_override_callout',
        ] as $column) {
            if (Schema::hasColumn('employees', $column)) {
                $employeeColumns[] = $column;
            }
        }

        if ($employeeColumns !== []) {
            Schema::table('employees', function (Blueprint $table) use ($employeeColumns): void {
                $table->dropColumn($employeeColumns);
            });
        }

        $productColumns = [];

        foreach ([
            'commission_rate_override_regular',
            'commission_rate_override_callout',
        ] as $column) {
            if (Schema::hasColumn('products', $column)) {
                $productColumns[] = $column;
            }
        }

        if ($productColumns !== []) {
            Schema::table('products', function (Blueprint $table) use ($productColumns): void {
                $table->dropColumn($productColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'daily_wage')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->decimal('daily_wage', 12, 2)->default(0)->after('bank_account');
            });
        }

        if (! Schema::hasColumn('employees', 'commission_rate_override_regular')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->decimal('commission_rate_override_regular', 5, 4)
                    ->nullable()
                    ->after('meal_allowance_per_day');
            });
        }

        if (! Schema::hasColumn('employees', 'commission_rate_override_callout')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->decimal('commission_rate_override_callout', 5, 4)
                    ->nullable()
                    ->after('commission_rate_override_regular');
            });
        }

        if (! Schema::hasColumn('products', 'commission_rate_override_regular')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('commission_rate_override_regular', 5, 4)
                    ->nullable()
                    ->after('is_active');
            });
        }

        if (! Schema::hasColumn('products', 'commission_rate_override_callout')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('commission_rate_override_callout', 5, 4)
                    ->nullable()
                    ->after('commission_rate_override_regular');
            });
        }
    }
};
