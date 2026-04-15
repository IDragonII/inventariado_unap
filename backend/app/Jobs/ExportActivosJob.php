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
            $archivo = $this->generarExcel();
            $export->update([
                'estado'  => 'completado',
                'archivo' => $archivo,
                'mensaje' => 'Exportación completada',
            ]);
        } catch (\Throwable $e) {
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
        $this->buildQuery()
            ->with(['area.oficina', 'edificio', 'responsable'])
            ->cursor()
            ->each(function ($activo) use ($writer, &$rowNum, $stylePar, $styleImpar) {
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
            });

        $writer->close();

        Storage::put($nombre, file_get_contents($rutaTmp));
        unlink($rutaTmp);

        return $nombre;
    }

    private function buildQuery()
    {
        $query = Activo::query()->withTrashed(false);

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

        return $query->orderBy('id');
    }
}