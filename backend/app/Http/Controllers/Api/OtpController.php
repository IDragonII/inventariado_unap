<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Otp;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function solicitar(Request $request)
    {
        $request->validate([
            'dni' => 'required'
        ]);

        $user = \App\Models\User::where('dni', $request->dni)->first();

        if (!$user) {
            return response()->json(['message' => 'DNI no encontrado'], 404);
        }

        // Usar correo del usuario o el que envió el frontend
        $correo = $user->email ?: $request->correo;

        // Si no hay correo en ningún lado, pedirlo
        if (!$correo) {
            return response()->json([
                'message'         => 'Sin correo registrado',
                'requiere_correo' => true,
            ], 200);
        }
        if (!str_ends_with($correo, '@unap.edu.pe')) {
            return response()->json([
                'message'         => 'Solo se permiten correos institucionales (@unap.edu.pe)',
                'requiere_correo' => true,
            ], 200);
        }

        $codigo    = rand(100000, 999999);
        $sessionId = Str::uuid()->toString();

        Otp::where('dni', $request->dni)->delete();

        Otp::create([
            'email'      => $correo,
            'dni'        => $request->dni,
            'code'       => $codigo,
            'expires_at' => Carbon::now()->addMinutes(5),
            'session_id' => $sessionId
        ]);

        Mail::html(
            view('emails.otp', ['codigo' => $codigo, 'nombre' => $user->name])->render(),
            fn($msg) => $msg->to($correo)->subject('Código de verificación - UNAP')
        );

        return response()->json([
            'message'    => 'Código enviado al correo',
            'session_id' => $sessionId
        ]);
    }

    public function verificar(Request $request)
    {
        $request->validate([
            'dni' => 'required',
            'otp' => 'required'
        ]);

        $otp = Otp::where('dni', $request->dni)
                   ->where('code', $request->otp)
                   ->first();

        if (!$otp) {
            return response()->json(['message' => 'Código incorrecto'], 400);
        }

        if (now()->greaterThan($otp->expires_at)) {
            return response()->json(['message' => 'Código expirado'], 400);
        }

        $user = \App\Models\User::where('dni', $request->dni)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($user) {
            if ($request->has('correo') && $request->correo) {
                $user->email = $request->correo;
                $user->save();
            } elseif ($otp->email && !$user->email) {
                $user->email = $otp->email;
                $user->save();
            }
        }

        $otp->delete();

        $user->tokens()->where('name', 'otp-token')->delete();

        $tokenTemporal = $user->createToken(
            'otp-token',
            ['consultar-activos', 'buscar-usuarios', 'buscar-oficinas', 'buscar-areas', 'crear-entrega'],
            now()->addHours(2)
        )->plainTextToken;

        return response()->json([
            'token_temporal' => $tokenTemporal,
            'usuario' => [
                'id'      => $user->id,
                'nombre'  => $user->name,
                'dni'     => $user->dni,
                'oficinas' => $user->oficinas->map(fn($o) => [
                    'id'     => $o->id,
                    'nombre' => $o->denominacion
                ])
            ]
        ]);
    }
}