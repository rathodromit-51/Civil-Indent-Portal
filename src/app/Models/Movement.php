<?php

namespace App\Models;

use App\Enums\MovementAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'indent_id',
        'from',
        'from_role',
        'to',
        'to_role',
        'action',
        'is_rejected',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'is_rejected' => 'boolean',
        ];
    }

    /**
     * Mutator to allow setting 'from' using either User model or CPF string.
     */
    public function setFromAttribute(mixed $value): void
    {
        $this->attributes['from'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * Mutator to allow setting 'to' using either User model or CPF string.
     */
    public function setToAttribute(mixed $value): void
    {
        $this->attributes['to'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * Mutator to allow setting action using MovementAction enum or string.
     */
    public function setActionAttribute(mixed $value): void
    {
        $this->attributes['action'] = $value instanceof MovementAction ? $value->value : (string) $value;
    }

    /**
     * The indent this movement belongs to.
     */
    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class);
    }

    /**
     * The user sending/originating this movement.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from', 'cpf');
    }

    /**
     * The user receiving this movement.
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to', 'cpf');
    }

    /**
     * Attachments uploaded during this specific movement/comment.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Role of the 'from' user at the time of movement, with fallback to user's active role.
     */
    public function getFromRoleNameAttribute(): string
    {
        return $this->from_role
            ?? $this->fromUser?->activeRole()?->name
            ?? $this->fromUser?->roleAssignment?->role?->name
            ?? $this->fromUser?->designation
            ?? 'User';
    }

    /**
     * Role of the 'to' user at the time of movement, with fallback to user's active role.
     */
    public function getToRoleNameAttribute(): string
    {
        return $this->to_role
            ?? $this->toUser?->activeRole()?->name
            ?? $this->toUser?->roleAssignment?->role?->name
            ?? $this->toUser?->designation
            ?? 'User';
    }
}
