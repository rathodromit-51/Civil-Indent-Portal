<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Indent;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'indent_id' => Indent::factory(),
            'movement_id' => null,
            'uploader' => fn () => User::factory()->create()->cpf,
            'original_name' => fake()->slug(2) . '.pdf',
            'stored_path' => 'attachments/' . fake()->uuid() . '.pdf',
            'size' => fake()->numberBetween(10240, 5242880), // 10 KB to 5 MB
            'mime_type' => 'application/pdf',
        ];
    }

    /**
     * Link attachment to a movement.
     */
    public function forMovement(Movement $movement): static
    {
        return $this->state(fn () => [
            'indent_id' => $movement->indent_id,
            'movement_id' => $movement->id,
            'uploader' => $movement->from ?? fn () => User::factory()->create()->cpf,
        ]);
    }
}
