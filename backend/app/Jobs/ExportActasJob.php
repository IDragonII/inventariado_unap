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
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

class ExportActasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        private int $exportId,
        private array $filtros
    ) {}

    public function handle(): void
    {
        $export = Export::find($this->exportId);
        if (!$export) return;

        try {
            $archivo = $this->generarExcel();
            $export->update([
                'estado' => 'completado',
                'archivo' => $archivo,
                'mensaje' => 'Exportación completada',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en ExportActasJob: ' . $e->getMessage());
            $export->update([
                'estado' => 'fallido',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function generarExcel(): string
    {
        $nombre = 'exports/actas_' . now()->format('Ymd_His') . '_' . $this->exportId . '.xlsx';
        $rutaTmp = sys_get_temp_dir() . '/' . basename($nombre);

        $writer = new Writer();
        $writer->openToFile($rutaTmp);
        
        $this->addDataTable($writer);

        $writer->close();
        Storage::put($nombre, file_get_contents($rutaTmp));
        unlink($rutaTmp);

        return $nombre;
    }
    
    private function addDataTable(Writer $writer): void
    {
        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(11);
        
        $headers = [
            'Código', 'Código Toma', 'Denominación', 'Tipo', 'Marca', 'Modelo', 
            'Número Serie', 'Dimensión', 'Área', 'Fecha Adquisición', 'Valor Inicial',
            'Estado', 'Condición', 'Descripción',
            'Oficina Código', 'Oficina Nombre',
            'Área Código', 'Área Nombre',
            'Edificio Código', 'Edificio Nombre',
            'Piso',
            'Responsable DNI', 'Responsable Nombre',
            'Teléfono', 'Declaración',
            'Tipo Acta', 'Número Acta', 'Fecha Acta'
        ];
        
        $writer->addRow(Row::fromValues($headers, $headerStyle));
        
        $activos = $this->getActivos();
        
        foreach ($activos->cursor() as $activo) {
            $writer->addRow(Row::fromValues([
                $activo->codigo ?? '',
                $activo->cod_toma ?? '',
                $activo->denominacion ?? '',
                $activo->tipo ?? '',
                $activo->marca ?? '',
                $activo->modelo ?? '',
                $activo->numero_serie ?? '',
                $activo->dimension ?? '',
                $activo->aula ?? '',
                $activo->fecha_adquisicion ?? '',
                $activo->valor_inicial ?? '',
                $activo->estado ?? '',
                $activo->condicion ?? '',
                $activo->descripcion ?? '',
                $activo->oficina_codigo ?? '',
                $activo->oficina_nombre ?? '',
                $activo->area_codigo ?? '',
                $activo->area_nombre ?? '',
                $activo->edificio_codigo ?? '',
                $activo->edificio_nombre ?? '',
                $activo->piso ?? '',
                $activo->responsable_dni ?? '',
                $activo->responsable_nombre ?? '',
                $activo->telefono ?? '',
                $activo->declaracion ?? '',
                $activo->origen ?? '',
                $activo->num_acta ?? '',
                $activo->fecha_acta ?? '',
            ]));
        }
    }
    
    private function getActivos()
    {
        $ids = $this->filtros['ids'] ?? null;
        
        $query = DB::table('activo_user as au')
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->leftJoin('areas as ar', 'a.area_id', '=', 'ar.id')
            ->leftJoin('oficinas as of', 'ar.oficina_id', '=', 'of.id')
            ->leftJoin('edificios as ed', 'a.edificio_id', '=', 'ed.id')
            ->leftJoin('users as r', 'a.responsable_id', '=', 'r.id')
            ->whereNotNull('au.num_acta')
            ->where('au.origen', 'acta')
            ->whereNull('a.deleted_at')
            ->whereNull('au.deleted_at')
            ->whereIn('au.id', function ($q) {
                $q->select(DB::raw('MAX(au2.id)'))
                    ->from('activo_user as au2')
                    ->whereNull('au2.deleted_at')
                    ->where('au2.origen', 'acta')
                    ->whereNotNull('au2.num_acta')
                    ->groupBy('au2.activo_id');
            })
            ->select(
                'a.codigo',
                'a.cod_toma',
                'a.denominacion',
                'a.tipo',
                'a.marca',
                'a.modelo',
                'a.numero_serie',
                'a.dimension',
                'ar.aula',
                'a.fecha_adquisicion',
                'a.valor_inicial',
                'a.estado',
                'a.condicion',
                'a.descripcion',
                'of.codigo as oficina_codigo',
                'of.denominacion as oficina_nombre',
                'ar.codigo as area_codigo',
                'ar.aula as area_nombre',
                'ed.codigo as edificio_codigo',
                'ed.denominacion as edificio_nombre',
                'a.piso',
                'r.dni as responsable_dni',
                'r.name as responsable_nombre',
                'a.telefono',
                'a.declaracion',
                'au.origen',
                'au.num_acta',
                'au.fecha as fecha_acta'
            );
        
        if ($ids !== null && $ids !== '') {
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                }
            }
            
            if (is_array($ids) && count($ids) > 0) {
                $query->whereIn('a.id', $ids);
            }
        }
        
        if (!empty($this->filtros['responsable_id'])) {
            $query->where('a.responsable_id', $this->filtros['responsable_id']);
        }

        if (!empty($this->filtros['area_id'])) {
            $query->where('a.area_id', $this->filtros['area_id']);
        }

        return $query->orderBy('au.num_acta');
    }
}