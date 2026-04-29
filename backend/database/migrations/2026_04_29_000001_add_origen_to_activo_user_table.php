<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activo_user', function (Blueprint $table) {
            $table->enum('origen', ['acta', 'importado', 'activo'])->nullable()->after('num_acta');
        });
    }

    public function down(): void
    {
        Schema::table('activo_user', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};