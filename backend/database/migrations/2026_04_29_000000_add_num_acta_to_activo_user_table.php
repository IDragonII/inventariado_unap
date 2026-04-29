<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activo_user', function (Blueprint $table) {
            $table->string('num_acta')->nullable()->after('item');
        });
    }

    public function down(): void
    {
        Schema::table('activo_user', function (Blueprint $table) {
            $table->dropColumn('num_acta');
        });
    }
};