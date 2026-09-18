<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\Notificacion;
use App\Models\User;
use Throwable;

class NotificacionService
{
    public function __construct(
        private readonly FcmService $fcmService
    ) {
    }

    /**
     * Envía una notificación a todos los usuarios
     * con dispositivos FCM activos.
     *
     * El usuario que envía la notificación:
     *
     * - SÍ recibe el registro en PostgreSQL
     * - NO recibe la notificación FCM
     */
    public function enviarATodos(
        string $titulo,
        string $mensaje,
        string $tipo = 'aviso',
        array $data = [],
        ?int $usuarioExcluido = null
    ): int {
        // ============================================================
        // USUARIOS CON FCM ACTIVO
        // ============================================================

        $usuarios = FcmToken::query()
            ->where('activo', true)
            ->whereNotNull('token')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        // ============================================================
        // ENVIAR A CADA USUARIO
        // ============================================================

        $enviados = 0;

        foreach ($usuarios as $usuarioId) {

            $esEmisor =
                $usuarioExcluido !== null &&
                (int) $usuarioId === $usuarioExcluido;

            $resultado = $this->enviarAUsuario(
                usuarioId: (int) $usuarioId,
                titulo: $titulo,
                mensaje: $mensaje,
                tipo: $tipo,
                data: $data,
                enviarFcm: !$esEmisor
            );

            if ($resultado > 0) {
                $enviados++;
            }
        }

        return $enviados;
    }

    /**
     * Envía una notificación solamente a los miembros
     * de la directiva:
     *
     * - secretario
     * - tesorero
     */
    public function enviarADirectiva(
        string $titulo,
        string $mensaje,
        string $tipo = 'aviso',
        array $data = []
    ): int {
        $usuarios = User::role([
            'Secretario',
            'Tesorero',
        ])
            ->whereHas('fcmTokens', function ($query) {
                $query
                    ->where('activo', true)
                    ->whereNotNull('token');
            })
            ->pluck('id');

        $enviados = 0;

        foreach ($usuarios as $usuarioId) {

            if ($this->enviarAUsuario(
                usuarioId: (int) $usuarioId,
                titulo: $titulo,
                mensaje: $mensaje,
                tipo: $tipo,
                data: $data
            ) > 0) {
                $enviados++;
            }
        }

        return $enviados;
    }

    /**
     * Envía una notificación a una lista de usuarios.
     */
    public function enviarAUsuarios(
        array $usuarioIds,
        string $titulo,
        string $mensaje,
        string $tipo = 'aviso',
        array $data = []
    ): int {
        $enviados = 0;

        foreach (array_unique($usuarioIds) as $usuarioId) {

            if ($this->enviarAUsuario(
                usuarioId: (int) $usuarioId,
                titulo: $titulo,
                mensaje: $mensaje,
                tipo: $tipo,
                data: $data
            ) > 0) {
                $enviados++;
            }
        }

        return $enviados;
    }

    /**
     * Guarda una notificación para el usuario
     * y opcionalmente la envía mediante FCM.
     *
     * $enviarFcm = true:
     * - guarda en PostgreSQL
     * - envía banner FCM si la preferencia lo permite
     *
     * $enviarFcm = false:
     * - guarda en PostgreSQL
     * - NO envía banner FCM
     */
    public function enviarAUsuario(
        int $usuarioId,
        string $titulo,
        string $mensaje,
        string $tipo = 'aviso',
        array $data = [],
        bool $enviarFcm = true
    ): int {

        // ============================================================
        // GUARDAR NOTIFICACIÓN EN POSTGRESQL
        // ============================================================

        $notificacion = Notificacion::create([
            'user_id' => $usuarioId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'data' => $data,
            'leida' => false,
        ]);

        // ============================================================
        // SI EL FLUJO NO DEBE ENVIAR FCM
        // ============================================================

        if (!$enviarFcm) {
            return 0;
        }

        // ============================================================
        // DETERMINAR LA PREFERENCIA SEGÚN EL TIPO
        // ============================================================

        $campoPreferencia = $this->obtenerCampoPreferencia($tipo);

        // ============================================================
        // OBTENER TOKENS FCM
        // ============================================================

        $consultaTokens = FcmToken::query()
            ->where('user_id', $usuarioId)
            ->where('activo', true)
            ->whereNotNull('token');

        /*
         * Si conocemos el tipo de notificación, aplicamos
         * la preferencia correspondiente.
         *
         * Ejemplo:
         *
         * Ingreso -> ingresos = true
         * Egreso  -> egresos = true
         * Zoe     -> zoe = true
         * Aviso   -> avisos = true
         */
        if ($campoPreferencia !== null) {
            $consultaTokens->where(
                $campoPreferencia,
                true
            );
        }

        $tokens = $consultaTokens
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        $enviados = 0;

        // ============================================================
        // ENVIAR FCM
        // ============================================================

        foreach ($tokens as $token) {

            try {

                $datos = array_merge(
                    $data,
                    [
                        'notificacion_id' =>
                            (string) $notificacion->id,

                        'tipo' =>
                            $tipo,
                    ]
                );

                $this->fcmService->enviarAUnToken(
                    token: $token,
                    titulo: $titulo,
                    mensaje: $mensaje,
                    data: $datos
                );

                $enviados++;

            } catch (Throwable $e) {

                $mensajeError =
                    $e->getMessage();

                $tokenInvalido =
                    str_contains(
                        $mensajeError,
                        'NotRegistered'
                    )
                    ||
                    str_contains(
                        $mensajeError,
                        'registration-token-not-registered'
                    );

                if ($tokenInvalido) {

                    FcmToken::query()
                        ->where(
                            'token',
                            $token
                        )
                        ->update([
                            'activo' => false,
                        ]);
                }
            }
        }

        return $enviados;
    }

    /**
     * Determina qué preferencia corresponde
     * a cada tipo de notificación.
     *
     * Devuelve:
     *
     * ingresos
     * egresos
     * zoe
     * avisos
     *
     * o null si el tipo no corresponde
     * a una preferencia específica.
     */
    private function obtenerCampoPreferencia(
        string $tipo
    ): ?string {

        $tipoNormalizado =
            strtolower(trim($tipo));

        return match ($tipoNormalizado) {

            'ingreso',
            'ingresos' =>
                'ingresos',

            'egreso',
            'egresos' =>
                'egresos',

            'zoe',
            'cierre_zoe',
            'cierre-periodo-zoe' =>
                'zoe',

            'aviso',
            'avisos',
            'aviso_vecinal',
            'aviso-vecinal',
            'avisos_vecinales',
            'avisos-vecinales' =>
                'avisos',

            default =>
                null,
        };
    }
}