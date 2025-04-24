<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->after('custom_rules', function (Blueprint $table): void {
                $table->double('ppa_balance_discount_percent', 5, 2)->nullable();
                $table->double('pif_balance_discount_percent', 5, 2)->nullable();
                $table->double('min_monthly_pay_percent', 5, 2)->nullable();
                $table->unsignedMediumInteger('max_days_first_pay')->nullable();
            });
        });
    }
};
