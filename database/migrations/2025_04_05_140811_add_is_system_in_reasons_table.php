<?php

declare(strict_types=1);

use App\Models\Reason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reasons', function (Blueprint $table): void {
            $table->boolean('is_system')->default(false)->after('label');
        });

        Reason::query()->update(['is_system' => true]);
    }
};
