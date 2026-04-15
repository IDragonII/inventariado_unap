<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OtpMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-OTP-TOKEN');

        if (!$token) {
            return response()->json(['message' => 'Token OTP requerido'], 401);
        }

        $data = cache()->get('otp_token_'.$token);

        if (!$data) {
            return response()->json(['message' => 'Token inválido o expirado'], 401);
        }

        // inyectar DNI al request
        $request->merge([
            'dni_otp' => $data['dni']
        ]);

        return $next($request);
    }
}