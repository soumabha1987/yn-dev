<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

$url = preg_replace('/(^https?:\/\/|\/$)/', '', config('app.url'));

Route::domain('creditor.' . $url)
    ->group(function (): void {
        Route::group([], base_path('routes/creditor/index.php'));
        Route::group([], base_path('routes/creditor/auth.php'));
        Route::group([], base_path('routes/creditor/superadmin.php'));
        Route::group([], base_path('routes/creditor/creditor.php'));
    });

Route::group(['domain' => 'consumer.' . $url], base_path('routes/consumer/index.php'));
