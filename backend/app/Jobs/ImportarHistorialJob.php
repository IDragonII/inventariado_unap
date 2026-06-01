<?php

namespace App\Jobs;

use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportarHistorialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        private int $exportId,
        private string $rutaArchivo
    ) {}

    public function handle(): void
    {
        $export = Export::find($this->exportId);
        if (!$export) return;

        try {
            $resultado = $this->importarExcel();

            $export->update([
                'estado' => 'completado',
                'mensaje' => "Importación completada: {$resultado['importados']} registros importados" .
                    ($resultado['errores'] > 0 ? ", {$resultado['errores']} errores" : ''),
                'filtros' => array_merge($export->filtros ?? [], [
                    'importados' => $resultado['importados'],
                    'errores' => $resultado['errores'],
                    'detalle_errores' => $resultado['detalle_errores'],
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en ImportarHistorialJob: ' . $e->getMessage());
            $export->update([
                'estado' => 'fallido',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function importarExcel(): array
    {
        $rutaCompleta = Storage::path($this->rutaArchivo);
        if (!file_exists($rutaCompleta)) {
            throw new \Exception("Archivo no encontrado: {$this->rutaArchivo}");
        }

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($rutaCompleta);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Fila 1 y 2 son headers, datos desde fila 3
        $importados = 0;
        $errores = [];
        $fila = 0;

        foreach ($rows as $row) {
            $fila++;
            if ($fila <= 2) continue; // saltar headers

            // Columna B = codigo_patrimonial (índice 1)
            $codigoPatrimonial = isset($row[1]) ? trim((string)$row[1]) : '';
            if ($codigoPatrimonial === '') continue;

            try {
                $this->procesarFila($row);
                $importados++;
            } catch (\Throwable $e) {
                $errores[] = "Fila {$fila}: " . $e->getMessage();
            }
        }

        return [
            'importados' => $importados,
            'errores' => count($errores),
            'detalle_errores' => array_slice($errores, 0, 20), // máximo 20 errores
        ];
    }

    private function procesarFila(array $row): void
    {
        // Mapear columnas (A=0, B=1, ..., P=15)
        $anioInventario     = isset($row[0])  ? (string)$row[0]  : null;
        $codigoPatrimonial  = isset($row[1])  ? trim((string)$row[1])  : '';
        $codigoAnterior     = isset($row[2])  ? (string)$row[2]  : null;
        $descripcion        = isset($row[3])  ? (string)$row[3]  : '';
        $dni                = isset($row[4])  ? (string)$row[4]  : null;
        $nombreResponsable  = isset($row[5])  ? (string)$row[5]  : null;
        $codigoOficina      = isset($row[6])  ? (string)$row[6]  : null;
        $codigoArea         = isset($row[7])  ? (string)$row[7]  : null;
        $nombreOficina      = isset($row[8])  ? (string)$row[8]  : null;
        $nombreArea         = isset($row[9])  ? (string)$row[9]  : null;
        $tomaHoja           = isset($row[10]) ? (string)$row[10] : null;
        $tomaOrden          = isset($row[11]) ? (string)$row[11] : null;
        $marca              = isset($row[12]) ? (string)$row[12] : null;
        $serie              = isset($row[13]) ? (string)$row[13] : null;
        $observacion        = isset($row[14]) ? (string)$row[14] : null;
        $estado             = isset($row[15]) ? (string)$row[15] : null;

        if ($codigoPatrimonial === '') {
            throw new \Exception("Código patrimonial vacío");
        }

        // ── 1) Buscar o crear oficina (primer nivel) ────────────────────
        $oficinaId = null;
        $codigoOficinaTrim = $codigoOficina ? trim((string)$codigoOficina) : '';
        $nombreOficinaTrim = $nombreOficina ? trim((string)$nombreOficina) : '';

        if ($codigoOficinaTrim !== '') {
            $oficina = DB::table('oficinas')->where('codigo', $codigoOficinaTrim)->first();
            if ($oficina) {
                $oficinaId = $oficina->id;
            } else {
                // Crear oficina con el codigo y nombre del Excel
                $oficinaId = DB::table('oficinas')->insertGetId([
                    'codigo'       => $codigoOficinaTrim,
                    'denominacion' => $nombreOficinaTrim !== '' ? $nombreOficinaTrim : $codigoOficinaTrim,
                    'escuela'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        } elseif ($nombreOficinaTrim !== '') {
            // Sin código: buscar/crear por denominacion
            $oficina = DB::table('oficinas')->where('denominacion', $nombreOficinaTrim)->first();
            if ($oficina) {
                $oficinaId = $oficina->id;
            } else {
                $oficinaId = DB::table('oficinas')->insertGetId([
                    'codigo'       => $nombreOficinaTrim,
                    'denominacion' => $nombreOficinaTrim,
                    'escuela'      => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // ── 2) Buscar o crear área dentro de la oficina ────────────────
        $areaId = null;
        $codigoAreaTrim = $codigoArea ? trim((string)$codigoArea) : '';
        $nombreAreaTrim = $nombreArea ? trim((string)$nombreArea) : '';

        if ($oficinaId) {
            if ($codigoAreaTrim !== '') {
                $area = DB::table('areas')
                    ->where('oficina_id', $oficinaId)
                    ->where('codigo', $codigoAreaTrim)
                    ->first();
                if ($area) {
                    $areaId = $area->id;
                } else {
                    // Crear área con codigo, aula y oficina_id
                    $areaId = DB::table('areas')->insertGetId([
                        'codigo'     => $codigoAreaTrim,
                        'aula'       => $nombreAreaTrim !== '' ? $nombreAreaTrim : $codigoAreaTrim,
                        'oficina_id' => $oficinaId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($nombreAreaTrim !== '') {
                // Sin código: buscar/crear por aula dentro de la oficina
                $area = DB::table('areas')
                    ->where('oficina_id', $oficinaId)
                    ->where('aula', $nombreAreaTrim)
                    ->first();
                if ($area) {
                    $areaId = $area->id;
                } else {
                    $areaId = DB::table('areas')->insertGetId([
                        'codigo'     => $nombreAreaTrim,
                        'aula'       => $nombreAreaTrim,
                        'oficina_id' => $oficinaId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // ── 3) Buscar o crear responsable por DNI ──────────────────────
        $responsableId = null;
        $dniTrim = $dni ? trim((string)$dni) : '';
        $nombreTrim = $nombreResponsable ? trim((string)$nombreResponsable) : '';

        if ($dniTrim !== '') {
            $user = DB::table('users')->where('dni', $dniTrim)->first();
            if ($user) {
                $responsableId = $user->id;
            } elseif ($nombreTrim !== '') {
                $responsableId = DB::table('users')->insertGetId([
                    'name'       => $nombreTrim,
                    'dni'        => $dniTrim,
                    'email'      => null,
                    'password'   => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 4) Buscar o crear el activo ─────────────────────────────────
        $activo = DB::table('activos')->where('codigo', $codigoPatrimonial)->first();
        if (!$activo) {
            $activoId = DB::table('activos')->insertGetId([
                'codigo'         => $codigoPatrimonial,
                'cod_toma'       => $codigoAnterior,
                'denominacion'   => $descripcion,
                'tipo'           => 'AF',
                'marca'          => $marca,
                'numero_serie'   => $serie,
                'descripcion'    => $observacion,
                'condicion'      => in_array($estado, ['N', 'B', 'R', 'M']) ? $estado : 'N',
                'estado'         => 'A',
                'area_id'        => $areaId,
                'responsable_id' => $responsableId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } else {
            $activoId = $activo->id;
            // Actualizar siempre con los datos de esta fila.
            // El Excel está ordenado ascendente por toma_orde, así que la última fila
            // procesada es la más actual y sus valores son los que quedan en el activo.
            $update = [];
            if ($areaId) {
                $update['area_id'] = $areaId;
            }
            if ($responsableId) {
                $update['responsable_id'] = $responsableId;
            }
            if (!empty($update)) {
                $update['updated_at'] = now();
                DB::table('activos')->where('id', $activoId)->update($update);
            }
        }

        // Convertir año si es numérico
        $anioInt = null;
        if ($anioInventario && is_numeric($anioInventario)) {
            $anioInt = (int)$anioInventario;
        }

        // ── 5) Insertar en historial ────────────────────────────────────
        DB::table('historial')->insert([
            'activo_id'              => $activoId,
            'anio_de_inventario'     => $anioInt,
            'codigo_patrimonial'     => $codigoPatrimonial,
            'codigo_anterior'        => $codigoAnterior,
            'descripcion'            => $descripcion,
            'dni'                    => $dni,
            'nombre_de_responsable'  => $nombreResponsable,
            'codigo_oficina'         => $codigoOficina,
            'codigo_area'            => $codigoArea,
            'nombre_oficina'         => $nombreOficina,
            'nombre_area'            => $nombreArea,
            'toma_hoja'              => $tomaHoja,
            'toma_orde'              => $tomaOrden,
            'marca'                  => $marca,
            'serie'                  => $serie,
            'observacion'            => $observacion,
            'estado'                 => $estado,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }
}
