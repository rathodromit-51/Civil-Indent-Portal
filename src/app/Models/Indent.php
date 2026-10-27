<?php

namespace App\Models;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Indent extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'title',
        'description',
        'work_type',
        'installation_id',
        'priority',
        'required_by',
        'justification',
        'status',
        'is_draft',
        'is_rejected',
        'creator_cpf',
        'creator',
        'current_holder_cpf',
        'current_holder',
    ];

    protected function casts(): array
    {
        return [
            'status' => IndentStatus::class,
            'priority' => IndentPriority::class,
            'required_by' => 'date',
            'is_draft' => 'boolean',
            'is_rejected' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Indent $indent) {
            if (empty($indent->number)) {
                $indent->number = static::generateIndentNumber();
            }
        });
    }

    /**
     * Safely generate a unique indent number like CI-2026-0001
     * using database transaction and row-locking to prevent duplicates under concurrent load.
     */
    public static function generateIndentNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');

        return DB::transaction(function () use ($year) {
            // Ensure sequence record exists for this year without race conditions
            DB::table('indent_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Lock row for update to guarantee concurrency safety
            $sequence = DB::table('indent_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $next = ($sequence->last_number ?? 0) + 1;

            DB::table('indent_sequences')
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return sprintf('CI-%04d-%04d', $year, $next);
        });
    }

    /**
     * Mutator to allow setting creator using either a User model or CPF string.
     */
    public function setCreatorAttribute(mixed $value): void
    {
        $this->attributes['creator_cpf'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * Mutator to allow setting current holder using either a User model or CPF string.
     */
    public function setCurrentHolderAttribute(mixed $value): void
    {
        $this->attributes['current_holder_cpf'] = $value instanceof User ? $value->cpf : $value;
    }

    /**
     * The installation for this indent.
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    /**
     * The user who created this indent.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_cpf', 'cpf');
    }

    /**
     * The current holder of this indent.
     */
    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_cpf', 'cpf');
    }

    /**
     * Movement history of this indent.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * The latest movement for this indent.
     */
    public function latestMovement(): HasOne
    {
        return $this->hasOne(Movement::class)->latestOfMany();
    }

    /**
     * Participants in this indent workflow.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(IndentParticipant::class);
    }

    /**
     * Direct User models participating in this indent.
     */
    public function participantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'indent_participants',
            'indent_id',
            'cpf',
            'id',
            'cpf'
        )->withTimestamps();
    }

    /**
     * Uploaded attachments for this indent.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Scope for draft indents.
     */
    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Scope for non-draft indents.
     */
    public function scopePublished($query)
    {
        return $query->where('is_draft', false);
    }

    /**
     * Scope by status.
     */
    public function scopeStatus($query, IndentStatus|string $status)
    {
        $val = $status instanceof IndentStatus ? $status->value : $status;

        return $query->where('status', $val);
    }

    /**
     * Scope by current holder CPF.
     */
    public function scopeForHolder($query, string $cpf)
    {
        return $query->where('current_holder_cpf', $cpf);
    }

    /**
     * Scope for indents held by user (Inbox "With me" page).
     */
    public function scopeWithMe($query, User|string $user)
    {
        $cpf = $user instanceof User ? $user->cpf : $user;

        return $query->where('current_holder_cpf', $cpf)->where('is_draft', false);
    }

    /**
     * Scope for indents flagged with rejection.
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_rejected', true);
    }

    /**
     * Determine if this indent is flagged with rejection.
     */
    public function isRejected(): bool
    {
        return (bool) ($this->is_rejected || $this->movements()->where('is_rejected', true)->exists());
    }

    /**
     * Get the next expected role in the strict sequence:
     * USER -> IM -> SAM -> SM -> HES -> LMW -> ICO -> CE
     */
    public function getNextExpectedRole(): ?string
    {
        $currentHolder = $this->currentHolder;
        if (! $currentHolder) {
            return 'IM';
        }

        $currentRole = $currentHolder->roleAssignment?->role?->name ?? 'User';

        return match ($currentRole) {
            'User' => 'IM',
            'IM' => 'SAM',
            'SAM' => 'SM',
            'SM' => 'HES',
            'HES' => 'LMW',
            'LMW', 'LM Works' => 'ICO',
            'ICO' => 'CE',
            default => null,
        };
    }

    /**
     * Determine if this indent is currently held by a Civil Engineer (CE).
     */
    public function isHeldByCe(): bool
    {
        return (bool) $this->currentHolder?->hasRole('CE');
    }

    /**
     * Get the latest rejection movement record if any.
     */
    public function latestRejectionMovement(): ?Movement
    {
        if ($this->relationLoaded('movements')) {
            return $this->movements
                ->where('is_rejected', true)
                ->sortByDesc('id')
                ->first();
        }

        return $this->movements()
            ->where('is_rejected', true)
            ->with(['fromUser.roleAssignment.role'])
            ->latest('created_at')
            ->latest('id')
            ->first();
    }
}
