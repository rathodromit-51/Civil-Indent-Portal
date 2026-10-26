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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indent_id')->constrained('indents')->cascadeOnDelete();
            $table->string('from', 14)->nullable()->index();
            $table->string('to', 14)->nullable()->index();
            $table->string('action', 50);
            $table->boolean('is_rejected')->default(false)->index();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
