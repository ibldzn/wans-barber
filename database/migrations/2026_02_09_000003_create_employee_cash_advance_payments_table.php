<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_cash_advance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_cash_advance_id')
                ->constrained('employee_cash_advances')
                ->cascadeOnDelete();
            $table->foreignId('payroll_period_id')
                ->nullable()
                ->constrained('payroll_periods')
                ->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['employee_cash_advance_id', 'paid_at'], 'idx_employee_cash_advance_payments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_cash_advance_payments');
    }
};
