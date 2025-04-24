<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomContentType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperCustomContent
 */
class CustomContent extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => CustomContentType::class,
    ];

    public function content(): Attribute
    {
        return Attribute::make(
            get: fn (?string $content): ?string => $content ? html_entity_decode($content) : null,
            set: fn (?string $content): ?string => $content ? htmlentities($content) : null
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }
}
