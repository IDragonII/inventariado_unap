<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivosExport implements FromQuery, WithHeadings, WithChunkReading, ShouldQueue
{
    use Exportable;

    public function query()
    {
        return DB::table('activos as a')
            ->select(
                'a.codigo', 'a.denominacion', 'a.marca', 'a.modelo', 'a.numero_serie',
                'a.piso', 'a.aula', 'a.descripcion', 'a.telefono', 'a.nombreInventariador',
                DB::raw("CASE a.condicion 
                            WHEN 'N' THEN 'nuevo' 
                            WHEN 'B' THEN 'bueno' 
                            WHEN 'R' THEN 'regular' 
                            WHEN 'M' THEN 'malo' 
                         END as condicion_nombre"),
                DB::raw("CASE a.estado 
                            WHEN 'activo' THEN 'U' 
                            ELSE 'D' 
                         END as estado_ud"),
                'u.dni as usuario_dni',
                'u.name as usuario_nombre',
                'ar.codigo as area_codigo',
                'ar.aula as area_aula',
                'ofc.codigo as oficina_codigo',
                'ofc.denominacion as oficina_denominacion',
                'ed.codigo as edificio_codigo',
                'ed.denominacion as edificio_denominacion',
                DB::raw("(SELECT grupo FROM activo_user WHERE activo_id = a.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) as grupo"),
                DB::raw("(SELECT item FROM activo_user WHERE activo_id = a.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) as item"),
                DB::raw("(SELECT fecha FROM activo_user WHERE activo_id = a.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) as fecha"),
                DB::raw("(SELECT num_acta FROM activo_user WHERE activo_id = a.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) as num_acta"),
                DB::raw("(SELECT origen FROM activo_user WHERE activo_id = a.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) as origen")
            )
            ->join('users as u', 'a.responsable_id', '=', 'u.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('oficinas as ofc', 'ar.oficina_id', '=', 'ofc.id')
            ->join('edificios as ed', 'a.edificio_id', '=', 'ed.id')
            ->whereNull('a.deleted_at')
            ->orderBy('a.id', 'asc');
    }
    public function map($activo): array
    {
        return [
            $activo->codigo,
            $activo->denominacion,
            $activo->marca,
            $activo->modelo,
            $activo->numero_serie,
            $activo->piso,
            $activo->aula,
            $activo->descripcion,
            $activo->telefono,
            $activo->nombreInventariador,
            $activo->condicion_nombre,
            $activo->estado_ud,
            $activo->usuario_dni,
            $activo->usuario_nombre,
            $activo->area_codigo,
            $activo->area_aula,
            $activo->oficina_codigo,
            $activo->oficina_denominacion,
            $activo->edificio_codigo,
            $activo->edificio_denominacion,
            $activo->grupo,
            $activo->item,
            $activo->fecha,
            $activo->num_acta,
            $activo->origen,
        ];
    }

    public function headings(): array
    {
        return [
            'Código', 'Denominación', 'Marca', 'Modelo', 'Número Serie', 'Piso', 'Aula',
            'Descripción', 'Teléfono', 'Inventariador', 'Condición', 'Estado UD',
            'DNI Usuario', 'Nombre Usuario', 'Código Área', 'Aula Área', 'Código Oficina',
            'Denominación Oficina', 'Código Edificio', 'Edificio', 'Grupo', 'Item', 'Fecha',
            'Num Acta', 'Origen'
        ];
    }
    

    public function chunkSize(): int
    {
        return 1000; // exporta en bloques de 1000 filas
    }
}
