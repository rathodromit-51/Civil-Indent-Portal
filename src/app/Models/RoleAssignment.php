<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'cpf',
        'area_id',
        'installation_id',
        'assigned_by',
        'from',
        'to',
    ];

    protected function casts(): array
    {
        return [
            'from' => 'date',
            'to' => 'date',
        ];
    }

    /**
     * The role being assigned.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Optional Area scope.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Optional Installation scope.
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    /**
     * The user holding this role assignment (by CPF).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cpf', 'cpf');
    }

    /**
     * The user who assigned this role (by CPF).
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'cpf');
    }
}
