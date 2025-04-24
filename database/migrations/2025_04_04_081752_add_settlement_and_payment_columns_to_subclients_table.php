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
        Schema::table('subclients', function (Blueprint $table): void {
            $table->after('max_days_first_pay', function (Blueprint $table): void {
                $table->double('minimum_settlement_percentage', 5, 2)->nullable();
                $table->double('minimum_payment_plan_percentage', 5, 2)->nullable();
                $table->unsignedSmallInteger('max_first_pay_days')->nullable();
            });
        });
    }
};
