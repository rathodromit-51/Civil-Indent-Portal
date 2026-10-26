<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'who',
        'what',
        'when',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'when' => 'datetime',
            'details' => 'array',
        ];
    }

    /**
     * The actor (user) who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'who', 'cpf');
    }
}
