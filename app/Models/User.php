<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'username',
        'email',
        'password',
        'role',
        'active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username) && ! empty($user->email)) {
                $base = Str::before($user->email, '@');
                $username = $base;
                $counter = 1;
                while (static::where('username', $username)->exists()) {
                    $username = $base.$counter++;
                }
                $user->username = $username;
            }

            if (empty($user->role)) {
                $user->role = 'ADMIN';
            }

            if (! isset($user->active)) {
                $user->active = true;
            }
        });
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['full_name'] ?? $this->attributes['username'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && (bool) $this->active;
    }

    public function getFilamentName(): string
    {
        return (string) ($this->full_name ?: $this->username ?: $this->email);
    }
}
