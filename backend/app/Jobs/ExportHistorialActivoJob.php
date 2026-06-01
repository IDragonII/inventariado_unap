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
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExportHistorialActivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        private int $exportId,
        private int $activoId
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
                'mensaje' => 'Historial exportado correctamente',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en ExportHistorialActivoJob: ' . $e->getMessage());
            $export->update([
                'estado' => 'fallido',
                'mensaje' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    private function generarExcel(): string
    {
        $nombre = 'exports/historial_activo_' . now()->format('Ymd_His') . '_' . $this->exportId . '.xlsx';
        $rutaTmp = sys_get_temp_dir() . '/' . basename($nombre);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Historial');

        $this->buildHeaders($sheet);
        $this->buildData($sheet);

        $writer = new Xlsx($spreadsheet);
        $writer->save($rutaTmp);

        Storage::put($nombre, file_get_contents($rutaTmp));
        unlink($rutaTmp);

        return $nombre;
    }

    private function buildHeaders($sheet): void
    {
        // ── Fila 1: headers principales ──────────────────────────────────────

        // Columnas simples que ocupan filas 1 y 2 (merge vertical)
        $simples = [
            'A' => 'AÑO DE INVENTARIO',
            'B' => 'CODIGO PATRIMONIAL',
            'C' => 'CODIGO ANTERIOR',
            'D' => 'DESCRIPCION',
            'E' => 'DNI',
            'F' => 'NOMBRE DE RESPONSABLE',
            'M' => 'MARCA',
            'N' => 'SERIE',
            'O' => 'OBSERVACION',
            'P' => 'ESTADO',
        ];

        foreach ($simples as $col => $titulo) {
            $sheet->setCellValue("{$col}1", $titulo);
            $sheet->mergeCells("{$col}1:{$col}2");
        }

        // Grupos que tienen sub-columnas
        $sheet->setCellValue('G1', 'CODIGO');
        $sheet->mergeCells('G1:H1');

        $sheet->setCellValue('I1', 'NOMBRE');
        $sheet->mergeCells('I1:J1');

        $sheet->setCellValue('K1', 'TOMA INVENTARIO');
        $sheet->mergeCells('K1:L1');

        // ── Fila 2: sub-headers de los grupos ────────────────────────────────
        $sheet->setCellValue('G2', 'OFICINA');
        $sheet->setCellValue('H2', 'AREA');
        $sheet->setCellValue('I2', 'OFICINA');
        $sheet->setCellValue('J2', 'AREA');
        $sheet->setCellValue('K2', 'HOJA');
        $sheet->setCellValue('L2', 'ORDEN');

        // ── Estilos de headers ────────────────────────────────────────────────
        $headerStyle = [
            'font' => [
                'bold'  => true,
                'size'  => 10,
                'name'  => 'Arial',
                'color' => ['argb' => 'FF000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9E1F2'], // azul claro similar a la imagen
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle('A1:P2')->applyFromArray($headerStyle);

        // ── Altura de filas de header ─────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Anchos de columna ─────────────────────────────────────────────────
        $anchos = [
            'A' => 10, // AÑO
            'B' => 16, // COD PATRIMONIAL
            'C' => 14, // COD ANTERIOR
            'D' => 30, // DESCRIPCION
            'E' => 12, // DNI
            'F' => 25, // NOMBRE RESPONSABLE
            'G' => 10, // COD OFICINA
            'H' => 10, // COD AREA
            'I' => 22, // NOMBRE OFICINA
            'J' => 22, // NOMBRE AREA
            'K' => 10, // HOJA
            'L' => 10, // ORDEN
            'M' => 12, // MARCA
            'N' => 16, // SERIE
            'O' => 18, // OBSERVACION
            'P' => 8,  // ESTADO
        ];

        foreach ($anchos as $col => $ancho) {
            $sheet->getColumnDimension($col)->setWidth($ancho);
        }
    }

    private function buildData($sheet): void
    {
        $registros = DB::table('historial')
            ->where('activo_id', $this->activoId)
            ->orderBy('anio_de_inventario', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $fila = 3; // los datos empiezan en la fila 3 (1 y 2 son headers)

        $dataStyle = [
            'font' => [
                'size' => 10,
                'name' => 'Arial',
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
        ];

        foreach ($registros as $registro) {
            $sheet->fromArray([
                $registro->anio_de_inventario    ?? '',
                $registro->codigo_patrimonial    ?? '',
                $registro->codigo_anterior       ?? '',
                $registro->descripcion           ?? '',
                $registro->dni                   ?? '',
                $registro->nombre_de_responsable ?? '',
                $registro->codigo_oficina        ?? '',
                $registro->codigo_area           ?? '',
                $registro->nombre_oficina        ?? '',
                $registro->nombre_area           ?? '',
                $registro->toma_hoja             ?? '',
                $registro->toma_orde             ?? '',
                $registro->marca                 ?? '',
                $registro->serie                 ?? '',
                $registro->observacion           ?? '',
                $registro->estado                ?? '',
            ], null, "A{$fila}");

            $sheet->getStyle("A{$fila}:P{$fila}")->applyFromArray($dataStyle);
            $sheet->getRowDimension($fila)->setRowHeight(18);

            $fila++;
        }
    }
}