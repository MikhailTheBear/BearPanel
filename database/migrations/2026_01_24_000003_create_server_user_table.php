<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role')->default('member'); // member|viewer|admin (потом расширим)
            $table->timestamps();

            $table->unique(['server_id', 'user_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_user');
    }
};
