<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperCsvHeader
 */
class CsvHeader extends Model
{
    use HasFactory;

    protected $casts = [
        'headers' => 'array',
        'is_mapped' => 'boolean',
    ];

    public function importHeaders(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->headers['import_headers'] ?? [],
            set: fn (array $value) => ['headers' => Json::encode([...($this->headers ?? []), 'import_headers' => $value])],
        );
    }

    public function mappedHeaders(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->headers['mapped_headers'] ?? [],
            set: fn (array $value) => ['headers' => Json::encode([...($this->headers ?? []), 'mapped_headers' => $value])],
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

    public function sftpConnection(): BelongsTo
    {
        return $this->belongsTo(SftpConnection::class);
    }
}
