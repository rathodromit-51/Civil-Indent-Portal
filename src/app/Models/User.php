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

    /**
     * Check if user has specific role(s) assigned.
     */
    public function hasRole(string|array $roles): bool
    {
        $roleName = $this->roleAssignment?->role?->name;
        if (! $roleName) {
            return false;
        }

        return is_array($roles) ? in_array($roleName, $roles, true) : $roleName === $roles;
    }

    /**
     * Get active role model.
     */
    public function activeRole(): ?Role
    {
        return $this->roleAssignment?->role;
    }

    /**
     * Get assigned Area if applicable.
     */
    public function assignedArea(): ?Area
    {
        return $this->roleAssignment?->area;
    }

    /**
     * Get assigned Installation if applicable.
     */
    public function assignedInstallation(): ?Installation
    {
        return $this->roleAssignment?->installation;
    }

    /**
     * Indents created by this user (by CPF).
     */
    public function createdIndents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Indent::class, 'creator_cpf', 'cpf');
    }

    /**
     * Indents currently held by this user (by CPF).
     */
    public function heldIndents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Indent::class, 'current_holder_cpf', 'cpf');
    }

    /**
     * Indents this user participates in.
     */
    public function participatingIndents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Indent::class,
            'indent_participants',
            'cpf',
            'indent_id',
            'cpf',
            'id'
        )->withTimestamps();
    }

    /**
     * Indent movements initiated by this user.
     */
    public function movementsFrom(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Movement::class, 'from', 'cpf');
    }

    /**
     * Indent movements received by this user.
     */
    public function movementsTo(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Movement::class, 'to', 'cpf');
    }

    /**
     * Attachments uploaded by this user.
     */
    public function uploadedAttachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attachment::class, 'uploader', 'cpf');
    }
}