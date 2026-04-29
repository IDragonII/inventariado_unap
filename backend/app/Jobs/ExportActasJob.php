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
            'Código', 'Denominación', 'Marca', 'Modelo', 'Número Serie',
            'Área', 'Responsable', 'DNI Responsable', 'Número Acta', 'Fecha'
        ];
        
        $writer->addRow(Row::fromValues($headers, $headerStyle));
        
        $activos = $this->getActivos();
        
        foreach ($activos->cursor() as $activo) {
            $writer->addRow(Row::fromValues([
                $activo->codigo ?? '',
                $activo->denominacion ?? '',
                $activo->marca ?? '',
                $activo->modelo ?? '',
                $activo->numero_serie ?? '',
                $activo->aula ?? '',
                $activo->responsable_nombre ?? '',
                $activo->responsable_dni ?? '',
                $activo->num_acta ?? '',
                $activo->fecha_registro ?? '',
            ]));
        }
    }
    
    private function getActivos()
    {
        $ids = $this->filtros['ids'] ?? null;
        
        $query = DB::table('activo_user as au')
            ->join('activos as a', 'au.activo_id', '=', 'a.id')
            ->join('areas as ar', 'a.area_id', '=', 'ar.id')
            ->join('users as r', 'a.responsable_id', '=', 'r.id')
            ->whereNotNull('au.num_acta')
            ->whereNull('a.deleted_at')
            ->whereNull('au.deleted_at')
            ->whereIn('au.id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('activo_user')
                    ->whereNull('deleted_at')
                    ->groupBy('activo_id');
            })
            ->select(
                'a.codigo',
                'a.denominacion',
                'a.marca',
                'a.modelo',
                'a.numero_serie',
                'ar.aula',
                'r.name as responsable_nombre',
                'r.dni as responsable_dni',
                'au.num_acta',
                'au.fecha as fecha_registro'
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