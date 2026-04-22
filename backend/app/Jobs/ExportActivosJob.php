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
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;

class ExportActivosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        private int   $exportId,
        private array $filtros
    ) {}

    public function handle(): void
    {
        $export = Export::find($this->exportId);
        if (!$export) return;

        try {
            Log::info('ExportActivosJob iniciando', ['filtros' => $this->filtros]);
            
            $archivo = $this->generarExcel();
            $export->update([
                'estado'  => 'completado',
                'archivo' => $archivo,
                'mensaje' => 'Exportación completada',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en ExportActivosJob: ' . $e->getMessage());
            $export->update([
                'estado'  => 'fallido',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function generarExcel(): string
    {
        $nombre  = 'exports/activos_' . now()->format('Ymd_His') . '_' . $this->exportId . '.xlsx';
        $rutaTmp = sys_get_temp_dir() . '/' . basename($nombre);

        $options = new Options();

        // Anchos de columna
        $anchos = [120, 250, 120, 120, 150, 70, 220, 70, 220, 70, 220, 100, 80, 70, 120, 80, 70, 220, 100, 200, 100, 200];
        foreach ($anchos as $i => $ancho) {
            $options->setColumnWidth($ancho, $i + 1);
        }

        $writer = new Writer($options);
        $writer->openToFile($rutaTmp);

        // ── Estilo header: fondo verde oscuro, texto blanco, negrita ──
        $borderPart  = new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID);
        $border      = new Border($borderPart);

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(11)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('1F6B35') // verde oscuro
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBorder($border)
            ->setShouldWrapText(false);

        $writer->addRow(Row::fromValues([
            'Código', 'Descripción', 'Marca', 'Modelo', 'N° Serie',
            'Cod CC', 'Centro de Costo', 'Cod Ubicación', 'Ubicación',
            'Cod Ed', 'Edificio', 'Piso', 'Ambiente', 'Situación',
            'Estado', 'Grupo', 'Toma ID', 'Descripción',
            'DNI', 'Responsable', 'Teléfono', 'Inventariador',
        ], $headerStyle));

        // ── Estilos filas alternas ──
        $stylePar = (new Style())
            ->setFontSize(10)
            ->setBackgroundColor('D9EAD3') // verde claro
            ->setShouldWrapText(false);

        $styleImpar = (new Style())
            ->setFontSize(10)
            ->setBackgroundColor(Color::WHITE)
            ->setShouldWrapText(false);

        $rowNum = 2;
        
        // Obtener los activos según los filtros
        $activos = $this->getActivos();
        $total = $activos->count();
        Log::info('Total de activos a exportar: ' . $total);
        
        foreach ($activos->cursor() as $activo) {
            $codigo = $activo->codigo;
            if (str_contains($codigo, '->')) {
                $codigo = explode('->', $codigo)[0];
            }

            $style = ($rowNum % 2 === 0) ? $stylePar : $styleImpar;

            $writer->addRow(Row::fromValues([
                $codigo,
                $activo->denominacion                  ?? '-',
                $activo->marca                         ?? '-',
                $activo->modelo                        ?? '-',
                $activo->numero_serie                  ?? '-',
                $activo->area?->oficina?->codigo       ?? '-',
                $activo->area?->oficina?->denominacion ?? '-',
                $activo->area?->codigo                 ?? '-',
                $activo->area?->aula                   ?? '-',
                $activo->edificio?->codigo             ?? '-',
                $activo->edificio?->denominacion       ?? '-',
                $activo->piso                          ?? '-',
                $activo->aula                          ?? '-',
                $activo->estado === 'activo' ? 'U'     : 'D',
                $activo->condicion                     ?? '-',
                $activo->cod_toma                      ?? '-',
                $activo->item                          ?? '-',
                $activo->descripcion                   ?? '',
                $activo->responsable?->dni             ?? 'FALTANTE',
                $activo->responsable?->name            ?? '-',
                $activo->telefono                      ?? '-',
                $activo->nombreInventariador           ?? '-',
            ], $style));

            $rowNum++;
        }

        $writer->close();

        Storage::put($nombre, file_get_contents($rutaTmp));
        unlink($rutaTmp);

        return $nombre;
    }

    private function getActivos()
    {
        $query = Activo::query()->with(['area.oficina', 'edificio', 'responsable']);
        
        // Verificar si hay IDs seleccionados explicitly
        $ids = $this->filtros['ids'] ?? null;
        
        Log::info('Filtros recibidos en job:', [
            'ids' => $ids,
            'tipo' => gettype($ids),
        ]);
        
        // Siempre priorizar IDs si existen y no están vacíos
        if ($ids !== null && $ids !== '') {
            // Si es string, intentar decodificar
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                }
            }
            
            // Verificar que sea un array con datos
            if (is_array($ids) && count($ids) > 0) {
                Log::info('Usando IDs seleccionados para exportar:', $ids);
                return $query->whereIn('id', $ids)->orderBy('id');
            }
        }
        
        // Si no hay IDs, usar filtros normales
        Log::info('Usando filtros normales para exportar');
        
        if (!empty($this->filtros['oficina_id'])) {
            $query->whereHas('area', fn($q) =>
                $q->where('oficina_id', $this->filtros['oficina_id'])
            );
        }

        if (!empty($this->filtros['area_id'])) {
            $query->where('area_id', $this->filtros['area_id']);
        }

        if (!empty($this->filtros['search'])) {
            $search = $this->filtros['search'];
            $query->where(fn($q) =>
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('denominacion', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%")
            );
        }

        if (!empty($this->filtros['responsable_id'])) {
            $query->where('responsable_id', $this->filtros['responsable_id']);
        }

        return $query->orderBy('id');
    }
}