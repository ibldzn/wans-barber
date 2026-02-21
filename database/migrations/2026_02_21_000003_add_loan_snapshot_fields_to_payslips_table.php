<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('loan_total_before', 12, 2)->default(0)->after('commission_total');
            $table->decimal('loan_installment_amount', 12, 2)->default(0)->after('loan_total_before');
            $table->decimal('loan_remaining_after', 12, 2)->default(0)->after('loan_installment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'loan_total_before',
                'loan_installment_amount',
                'loan_remaining_after',
            ]);
        });
    }
};
