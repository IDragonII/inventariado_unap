<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('historial');

        Schema::create('historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activo_id');
            $table->year('anio_de_inventario')->nullable();
            $table->string('codigo_patrimonial');
            $table->string('codigo_anterior')->nullable();
            $table->string('descripcion');
            $table->string('dni')->nullable();
            $table->string('nombre_de_responsable')->nullable();
            $table->string('codigo_oficina')->nullable();
            $table->string('codigo_area')->nullable();
            $table->string('nombre_oficina')->nullable();
            $table->string('nombre_area')->nullable();
            $table->string('toma_hoja')->nullable();
            $table->string('toma_orde')->nullable();
            $table->string('marca')->nullable();
            $table->string('serie')->nullable();
            $table->string('observacion')->nullable();
            $table->string('estado')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('activo_id')
                  ->references('id')
                  ->on('activos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial');
    }
};
