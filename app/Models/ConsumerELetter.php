<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperConsumerELetter
 */
class ConsumerELetter extends Pivot
{
    use HasFactory;

    public $incrementing = true;

    protected $casts = [
        'enabled' => 'boolean',
        'read_by_consumer' => 'boolean',
    ];

    public function eLetter(): BelongsTo
    {
        return $this->belongsTo(ELetter::class);
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }
}
