<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_cards', function (Blueprint $table) {
            $table->foreignId('consumer_profile_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
        });
    }


};
