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
        Schema::table('file_upload_histories', function (Blueprint $table): void {
            $table->boolean('is_hidden')->default(false)->after('is_sftp_import');
        });
    }
};
