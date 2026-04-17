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
        'correo' => 'required|email',
        'dni' => 'required'
    ]);

    $codigo = rand(100000, 999999);

    $sessionId = Str::uuid()->toString();

    Otp::where('dni', $request->dni)->delete();

    Otp::create([
        'email' => $request->correo,
        'dni' => $request->dni,
        'code' => $codigo,
        'expires_at' => Carbon::now()->addMinutes(5),
        'session_id' => $sessionId
    ]);

    Mail::raw("Tu código OTP es: $codigo", function ($msg) use ($request) {
        $msg->to($request->correo)
            ->subject('Código OTP');
    });

    return response()->json([
        'message' => 'Código enviado al correo',
        'session_id' => $sessionId
    ]);
}

    public function verificar(Request $request)
{
    $request->validate([
        'correo' => 'required|email',
        'dni'    => 'required',
        'otp'    => 'required'
    ]);

    $otp = Otp::where('dni', $request->dni)
        ->where('email', $request->correo)
        ->where('code', $request->otp)
        ->first();

    if (!$otp) {
        return response()->json(['message' => 'Código incorrecto'], 400);
    }

    if (now()->greaterThan($otp->expires_at)) {
        return response()->json(['message' => 'Código expirado'], 400);
    }

    $otp->delete();

    // Buscar el usuario por DNI
    $user = \App\Models\User::where('dni', $request->dni)->first();

    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    // Revocar tokens OTP anteriores del mismo usuario
    $user->tokens()->where('name', 'otp-token')->delete();

    // Crear token temporal con habilidades limitadas (expira en 2 horas)
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