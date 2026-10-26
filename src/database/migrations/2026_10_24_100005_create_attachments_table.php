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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indent_id')->constrained('indents')->cascadeOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('movements')->nullOnDelete();
            $table->string('uploader', 14)->index();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->unsignedBigInteger('size');
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
