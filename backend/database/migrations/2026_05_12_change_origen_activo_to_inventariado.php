<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activo_user MODIFY COLUMN origen ENUM('acta', 'importado', 'inventariado', 'activo') DEFAULT NULL");
        
        DB::table('activo_user')
            ->where('origen', 'activo')
            ->whereNotNull('num_acta')
            ->update(['origen' => 'acta']);
        
        DB::table('activo_user')
            ->where('origen', 'activo')
            ->update(['origen' => 'inventariado']);
        
        DB::statement("ALTER TABLE activo_user MODIFY COLUMN origen ENUM('acta', 'importado', 'inventariado') DEFAULT 'inventariado'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activo_user MODIFY COLUMN origen ENUM('acta', 'importado', 'inventariado', 'activo') DEFAULT NULL");
        
        DB::table('activo_user')
            ->where('origen', 'acta')
            ->update(['origen' => 'activo']);
        
        DB::table('activo_user')
            ->where('origen', 'inventariado')
            ->update(['origen' => 'activo']);
        
        DB::statement("ALTER TABLE activo_user MODIFY COLUMN origen ENUM('acta', 'importado', 'activo') DEFAULT NULL");
    }
};
