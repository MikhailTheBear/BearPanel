<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fqdn');
            $table->string('scheme')->default('http'); // http / https
            $table->unsignedSmallInteger('daemon_port')->default(8080);
            $table->string('token')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['fqdn', 'daemon_port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
