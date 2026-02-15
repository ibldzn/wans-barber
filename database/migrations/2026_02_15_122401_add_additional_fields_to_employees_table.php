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
            $table->string('emp_citizen_id')->nullable()->after('emp_name');
            $table->string('emp_address')->nullable()->after('emp_citizen_id');
            // 14 April 2023 (kecuali Juni, Joni, Sukim, Nur, Arif)
            $table->date('emp_join_date')->nullable()->after('emp_address');
            $table->string('bank_name')->nullable()->after('bank_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('emp_citizen_id');
            $table->dropColumn('emp_address');
            $table->dropColumn('emp_join_date');
            $table->dropColumn('bank_name');
        });
    }
};
