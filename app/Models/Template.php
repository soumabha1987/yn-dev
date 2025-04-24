<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperTemplate
 */
class Template extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'type' => TemplateType::class,
    ];

    /**
     * @return Attribute<?string, ?string>
     */
    public function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $description): ?string => $description ? html_entity_decode($description) : null,
            set: fn (?string $description): ?string => $description ? htmlentities($description) : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
