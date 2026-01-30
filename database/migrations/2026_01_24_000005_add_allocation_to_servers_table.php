<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('allocation_port')->nullable()->unique()->after('container_id');
            $table->string('data_path')->nullable()->after('allocation_port');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropUnique(['allocation_port']);
            $table->dropColumn(['allocation_port', 'data_path']);
        });
    }
};
