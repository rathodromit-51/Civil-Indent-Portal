<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'indent_id',
        'movement_id',
        'uploader',
        'original_name',
        'stored_path',
        'size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Mutator to allow setting 'uploader' using either User model or CPF string.
     */
    public function setUploaderAttribute(mixed $value): void
    {
        $this->attributes['uploader'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * The indent this attachment belongs to.
     */
    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class);
    }

    /**
     * Optional movement/comment this attachment was attached with.
     */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    /**
     * The user who uploaded this attachment.
     */
    public function uploaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader', 'cpf');
    }

    /**
     * Human readable file size helper.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
