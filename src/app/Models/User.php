<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'cpf',
        'designation',
        'location',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Active role assignment for this user by CPF.
     */
    public function roleAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoleAssignment::class, 'cpf', 'cpf');
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->designation === 'Super Admin'
            || optional($this->roleAssignment?->role)->name === 'Super Admin';
    }
}