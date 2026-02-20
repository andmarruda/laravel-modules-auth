<?php

namespace Andmarruda\AuthModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Andmarruda\AuthModule\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_manager',
        'article_coins_balance',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_manager' => 'boolean',
            'article_coins_balance' => 'integer',
            'profile_completed_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user is a manager.
     * 
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->is_manager ?? false;
    }

    /**
     * Create a new factory instance for the model.
     * 
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function getPreference(string $key, ?string $default = null): ?string
    {
        /** @var UserPreference|null $preference */
        $preference = $this->preferences()
            ->where('key', $key)
            ->first();

        return $preference?->value ?? $default;
    }

    public function setPreference(string $key, ?string $value): void
    {
        $this->preferences()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
