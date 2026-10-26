<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indents', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('work_type');
            $table->foreignId('installation_id')->constrained('installations')->cascadeOnDelete();
            $table->string('priority', 50)->default('Medium');
            $table->date('required_by');
            $table->text('justification')->nullable();
            $table->string('status', 50)->default('Open')->index();
            $table->boolean('is_draft')->default(false)->index();
            $table->string('creator_cpf', 14)->index();
            $table->string('current_holder_cpf', 14)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indents');
    }
};
