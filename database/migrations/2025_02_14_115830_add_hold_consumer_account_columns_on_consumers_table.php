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
        Schema::table('consumers', function (Blueprint $table): void {
            $table->after('pass_through5', function (Blueprint $table): void {
                $table->date('restart_date')->nullable();
                $table->string('hold_reason', 255)->nullable();
            });
        });
    }
};
