<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('name');

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('nodes')->cascadeOnDelete();

            $table->string('status')->default('stopped'); // installing|running|stopped|suspended
            $table->json('limits')->nullable(); // cpu/ram/disk etc

            $table->timestamps();

            $table->index(['owner_id']);
            $table->index(['node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
