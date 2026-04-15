<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_exports_table.php
public function up(): void
{
    Schema::create('exports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('estado')->default('procesando'); // procesando, completado, fallido
        $table->string('archivo')->nullable();           // ruta en storage
        $table->json('filtros')->nullable();             // filtros aplicados
        $table->string('mensaje')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
