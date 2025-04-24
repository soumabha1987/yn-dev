<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedCards extends Model
{
    use HasFactory;

    protected $casts = [];
    protected $fillable = [
        'consumer_id',
        'last4digit',
        'card_holder_name',
        'expiry',
        'encrypted_card_data'
    ];
}
