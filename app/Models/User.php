<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPasswordQueuedNotification;
use App\Notifications\VerifyEmailQueuedNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $casts = [
        'blocked_at' => 'datetime',
        'is_h2h_user' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subclient(): BelongsTo
    {
        return $this->belongsTo(Subclient::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function users(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->users()
            ->with(['parent', 'company:id,company_name', 'subclient:id,subclient_name', __FUNCTION__])
            ->withTrashed();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailQueuedNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordQueuedNotification($token));
    }

    public function blockerUser(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }
}
