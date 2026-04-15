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
            'dni' => 'required',
            'otp' => 'required'
        ]);

        $otp = Otp::where('dni', $request->dni)
            ->where('email', $request->correo)
            ->where('code', $request->otp)
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Código incorrecto'
            ], 400);
        }

        if (now()->greaterThan($otp->expires_at)) {
            return response()->json([
                'message' => 'Código expirado'
            ], 400);
        }

        $otp->delete();

        $token = Str::random(60);

        cache()->put('otp_token_'.$token, [
            'dni' => $request->dni
        ], now()->addMinutes(15));

        return response()->json([
            'token' => $token
        ]);
    }
}