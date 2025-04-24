<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ELetterType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperELetter
 */
class ELetter extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'disabled' => 'boolean',
        'type' => ELetterType::class,
    ];

    public function message(): Attribute
    {
        return Attribute::make(
            get: fn (?string $message): ?string => $message ? html_entity_decode($message) : null,
            set: fn (?string $message): ?string => $message ? htmlentities($message) : null,
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

    public function consumers(): BelongsToMany
    {
        return $this->belongsToMany(Consumer::class)
            ->withPivot(['read_by_consumer', 'enabled'])
            ->withTimestamps();
    }

    public function consumerELetters(): HasMany
    {
        return $this->hasMany(ConsumerELetter::class);
    }
}
