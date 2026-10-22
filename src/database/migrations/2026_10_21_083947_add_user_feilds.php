<?php
// database/migrations/xxxx_xx_xx_update_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 14)->unique()->after('id');
            $table->string('designation')->after('name');
            $table->string('location')->after('designation');
            $table->string('email')->nullable()->change();
            $table->boolean('active')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['designation', 'location', 'active']);
            $table->string('email')->nullable(false)->change();
        });
    }
};