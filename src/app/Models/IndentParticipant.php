<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'indent_id',
        'cpf',
    ];

    /**
     * Mutator to allow setting 'cpf' using either User model or CPF string.
     */
    public function setCpfAttribute(mixed $value): void
    {
        $this->attributes['cpf'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * The indent this participation belongs to.
     */
    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class);
    }

    /**
     * The participant user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cpf', 'cpf');
    }
}
