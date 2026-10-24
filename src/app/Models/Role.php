<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scope_type',
        'multi_holder',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'multi_holder' => 'boolean',
        ];
    }

    /**
     * Role assignments for this role.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
}
