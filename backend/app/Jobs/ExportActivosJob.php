<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Inventariado\Activo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

class ExportActivosJob implements ShouldQueue
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
            Log::error('Error en ExportActivosJob: ' . $e->getMessage());
            $export->update([
                'estado' => 'fallido',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function generarExcel(): string
    {
        $nombre = 'exports/activos_' . now()->format('Ymd_His') . '_' . $this->exportId . '.xlsx';
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
            'codigo', 'cod_toma', 'denominacion', 'tipo', 'marca', 'modelo', 
            'numero_serie', 'dimension', 'aula', 'fecha_adquisicion', 'valor_inicial',
            'estado', 'condicion', 'descripcion', 
            'oficina_codigo', 'oficina_nombre',
            'area_codigo', 'area_nombre',
            'edificio_codigo', 'edificio_nombre',
            'piso', 
            'responsable_dni', 'responsable_nombre',
            'telefono', 'declaracion', 
            'dni_inventariador', 'nombre_inventariador',
            'tipo_acta'
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
                $this->getDate($activo->fecha_adquisicion),
                $this->getCurrency($activo->valor_inicial),
                $activo->estado ?? '',
                $activo->condicion ?? '',
                $activo->descripcion ?? '',
                $activo->area?->oficina?->codigo ?? '',
                $activo->area?->oficina?->denominacion ?? '',
                $activo->area?->codigo ?? '',
                $activo->area?->aula ?? '',
                $activo->edificio?->codigo ?? '',
                $activo->edificio?->denominacion ?? '',
                $activo->piso ?? '',
                $activo->responsable?->dni ?? '',
                $activo->responsable?->name ?? '',
                $activo->telefono ?? '',
                $activo->declaracion ?? '',
                $activo->dniInventariador ?? '',
                $activo->nombreInventariador ?? '',
                $this->getTipoActa($activo->id),
            ]));
        }
    }
    
    private function getCurrency($v): string
    {
        if (!$v) return '';
        return number_format((float)$v, 2, '.', '');
    }
    
    private function getDate($f): string
    {
        if (!$f) return '';
        try {
            return Carbon::parse($f)->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    private function getTipoActa(int $activoId): string
    {
        $pivot = DB::table('activo_user')
            ->where('activo_id', $activoId)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();
        return $pivot->origen ?? '';
    }

    private function getActivos()
    {
        $query = Activo::query()->with(['area.oficina', 'edificio', 'responsable']);
        
        $ids = $this->filtros['ids'] ?? null;
        
        if ($ids !== null && $ids !== '') {
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                }
            }
            
            if (is_array($ids) && count($ids) > 0) {
                return $query->whereIn('id', $ids)->orderBy('codigo');
            }
        }
        
        if (!empty($this->filtros['oficina_id'])) {
            $query->whereHas('area', fn($q) => $q->where('oficina_id', $this->filtros['oficina_id']));
        }

        if (!empty($this->filtros['area_id'])) {
            $query->where('area_id', $this->filtros['area_id']);
        }

        if (!empty($this->filtros['search'])) {
            $search = $this->filtros['search'];
            $query->where(fn($q) => $q->where('codigo', 'like', "%{$search}%")
                ->orWhere('denominacion', 'like', "%{$search}%")
                ->orWhere('numero_serie', 'like', "%{$search}%"));
        }

        return $query->orderBy('codigo');
    }
}
