<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetApiController extends Controller
{
    /**
     * Paso 1: Generar código OTP de 6 dígitos y enviarlo al correo del usuario.
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No encontramos ninguna cuenta asociada a este correo electrónico.',
            ], 404);
        }

        // Generar código numérico de 6 dígitos
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar o actualizar el token hasheado en password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => Carbon::now(),
            ]
        );

        // Enviar correo con el código
        Mail::raw(
            "Hola {$user->name},\n\n" .
            "Recibimos una solicitud para restablecer tu contraseña en SIGEFIV.\n" .
            "Tu código de verificación es: {$code}\n\n" .
            "Este código expirará en 15 minutos. Si no solicitaste este cambio, ignora este mensaje.",
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Código de recuperación de contraseña - SIGEFIV');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Hemos enviado un código de 6 dígitos a tu correo electrónico.',
        ]);
    }

    /**
     * Paso 2: Verificar el código OTP y actualizar la contraseña (sirve también para vincular cuentas de Google/Facebook).
     */
    public function resetWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->code, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'El código ingresado es incorrecto.',
            ], 422);
        }

        // Verificar expiración de 15 minutos
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Por favor, solicita uno nuevo.',
            ], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Eliminar el registro usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
    }
}