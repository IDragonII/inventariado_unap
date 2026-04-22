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
use Illuminate\Support\Carbon;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;

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
        
        $this->addTitle($writer);
        $this->addFiltersInfo($writer);
        $this->addDataTable($writer);
        $this->addFooter($writer);

        $writer->close();
        Storage::put($nombre, file_get_contents($rutaTmp));
        unlink($rutaTmp);

        return $nombre;
    }
    
    private function addTitle(Writer $writer): void
    {
        $titleStyle = (new Style())
            ->setFontBold()
            ->setFontSize(16);
            
        $dateStyle = (new Style())
            ->setFontSize(9);
        
        $writer->addRow(Row::fromValues(['INVENTARIO DE ACTIVOS FIJOS - UNAP'], $titleStyle));
        $writer->addRow(Row::fromValues(['Universidad Nacional del Altiplano de Puno']));
        $writer->addRow(Row::fromValues(['Fecha: ' . Carbon::now()->format('d/m/Y H:i:s')], $dateStyle));
        $writer->addRow(Row::fromValues(['']));
    }
    
    private function addFiltersInfo(Writer $writer): void
    {
        $filters = [];
        
        if (!empty($this->filtros['oficina_id'])) {
            $filters[] = 'Oficina ID: ' . $this->filtros['oficina_id'];
        }
        if (!empty($this->filtros['area_id'])) {
            $filters[] = 'Área ID: ' . $this->filtros['area_id'];
        }
        if (!empty($this->filtros['search'])) {
            $filters[] = 'Búsqueda: ' . $this->filtros['search'];
        }
        
        if (!empty($filters)) {
            $filterStyle = (new Style())->setFontBold();
            $writer->addRow(Row::fromValues(['Filtros aplicados:'], $filterStyle));
            $writer->addRow(Row::fromValues($filters));
            $writer->addRow(Row::fromValues(['']));
        }
    }
    
    private function addDataTable(Writer $writer): void
    {
        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(10);
        
        $rowPar = (new Style())->setFontSize(9);
        $rowImpar = (new Style())->setFontSize(9);
        
        $headers = [
            'Código', 'Denominación', 'Marca', 'Modelo', 'N° Serie',
            'Código CC', 'Centro de Costo', 'Código Ubicación', 'Ubicación',
            'Código Edificio', 'Edificio', 'Piso', 'Estado', 'Condición',
            'Código Toma', 'Item', 'DNI Responsable', 'Responsable',
            'Teléfono', 'Valor Inicial', 'Fecha Adquisición'
        ];
        
        $writer->addRow(Row::fromValues($headers, $headerStyle));
        
        $activos = $this->getActivos();
        $total = $activos->count();
        
        $totalStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues(['Total de registros: ' . $total], $totalStyle));
        $writer->addRow(Row::fromValues(['']));
        
        $rowNum = 1;
        foreach ($activos->cursor() as $activo) {
            $rowNum++;
            $style = ($rowNum % 2 === 0) ? $rowPar : $rowImpar;
            
            $writer->addRow(Row::fromValues([
                $this->clearCode($activo->codigo),
                $activo->denominacion ?? '-',
                $activo->marca ?? '-',
                $activo->modelo ?? '-',
                $activo->numero_serie ?? '-',
                $activo->area?->oficina?->codigo ?? '-',
                $activo->area?->oficina?->denominacion ?? '-',
                $activo->area?->codigo ?? '-',
                $activo->area?->aula ?? '-',
                $activo->edificio?->codigo ?? '-',
                $activo->edificio?->denominacion ?? '-',
                $activo->piso ?? '-',
                $this->getEstado($activo->estado),
                $this->getCondicion($activo->condicion),
                $activo->cod_toma ?? '-',
                $activo->item ?? '-',
                $activo->responsable?->dni ?? 'SIN ASIGNAR',
                $activo->responsable?->name ?? 'SIN ASIGNAR',
                $activo->telefono ?? '-',
                $this->getCurrency($activo->valor_inicial),
                $this->getDate($activo->fecha_adquisicion),
            ], $style));
        }
    }
    
    private function addFooter(Writer $writer): void
    {
        $footerStyle = (new Style())->setFontSize(8);
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Sistema de Gestión de Activos - UNAP'], $footerStyle));
    }
    
    private function clearCode(?string $code): string
    {
        if (!$code) return '-';
        return str_contains($code, '->') ? explode('->', $code)[0] : $code;
    }
    
    private function getEstado(?string $s): string
    {
        return match($s) {
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
            default => strtoupper($s ?? '-')
        };
    }
    
    private function getCondicion(?string $c): string
    {
        return match($c) {
            'nuevo' => 'Nuevo',
            'bueno' => 'Bueno',
            'regular' => 'Regular',
            'malo' => 'Malo',
            default => strtoupper($c ?? '-')
        };
    }
    
    private function getCurrency($v): string
    {
        return $v ? 'S/ ' . number_format((float)$v, 2, '.', ',') : 'S/ 0.00';
    }
    
    private function getDate($f): string
    {
        if (!$f) return '-';
        try {
            return Carbon::parse($f)->format('d/m/Y');
        } catch (\Exception $e) {
            return '-';
        }
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