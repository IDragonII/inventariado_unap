<?php

namespace App\Http\Controllers\Inventariado;

use App\Http\Controllers\BaseController;
use App\Jobs\ImportarHistorialJob;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HistorialController extends BaseController
{
    public function importar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'archivo' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
            ]);

            if ($validator->fails()) {
                return $this->handleException(
                    new \Exception($validator->errors()->first())
                );
            }

            $archivo = $request->file('archivo');
            $nombre = 'imports/historial_' . now()->format('Ymd_His') . '_' . uniqid() . '.xlsx';
            Storage::put($nombre, file_get_contents($archivo->getRealPath()));

            $export = Export::create([
                'user_id' => auth()->id(),
                'estado'  => 'procesando',
                'filtros' => [
                    'archivo' => $nombre,
                    'tipo'    => 'importar_historial',
                ],
                'mensaje' => 'Procesando archivo de historial...',
            ]);

            ImportarHistorialJob::dispatch($export->id, $nombre);

            return response()->json(['export_id' => $export->id], 202);
        } catch (\Throwable $e) {
            Log::error('Error al iniciar importación de historial: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    public function statusImport(int $id)
    {
        $export = Export::where('id', $id)
                        ->where('user_id', auth()->id())
                        ->firstOrFail();

        $url = null;
        if ($export->estado === 'completado' && $export->archivo) {
            $url = Storage::temporaryUrl($export->archivo, now()->addMinutes(30));
        }

        return response()->json([
            'estado'  => $export->estado,
            'mensaje' => $export->mensaje,
            'url'     => $url,
            'filtros' => $export->filtros,
        ]);
    }

    public function eliminarImport(int $id)
    {
        $export = Export::where('id', $id)
                        ->where('user_id', auth()->id())
                        ->firstOrFail();

        if ($export->archivo && Storage::exists($export->archivo)) {
            Storage::delete($export->archivo);
        }

        $export->delete();

        return response()->json(['ok' => true]);
    }
}
