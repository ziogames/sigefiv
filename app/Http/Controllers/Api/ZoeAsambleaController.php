<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZoeAsambleaController extends Controller
{
    
/**
     * Obtiene o crea la conversación activa de Zoe para el usuario.
     */
    private function obtenerConversacionId($userId)
    {
        // 1. Buscar la última conversación activa usando usuario_id
        $conversacion = DB::table('zoe_conversaciones')
            ->where('usuario_id', $userId) // 👈 Cambiado a usuario_id
            ->orderBy('id', 'desc')
            ->first();

        if ($conversacion) {
            return $conversacion->id;
        }

        // 2. Si no existe, crear una nueva
        return DB::table('zoe_conversaciones')->insertGetId([
            'usuario_id' => $userId,      // 👈 Cambiado a usuario_id
            'titulo' => 'Conversación con Zoe',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function verificarAvisosPendientes(Request $request)
    {
        $userId = $request->user()->id;
        $ahora = Carbon::now();

        // 1. Obtener la próxima asamblea activa y publicada (desde hoy hasta 5 días en adelante)
        $asambleas = DB::table('asambleas')
            ->where('estado', 'publicada')
            ->whereDate('fecha', '>=', $ahora->toDateString())
            ->whereDate('fecha', '<=', $ahora->copy()->addDays(5)->toDateString())
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();

        $asambleaProxima = null;
        $fechaHoraAsamblea = null;

        foreach ($asambleas as $asamblea) {
            $momento = Carbon::parse("{$asamblea->fecha} {$asamblea->hora}");
            if ($momento->greaterThan($ahora)) {
                $asambleaProxima = $asamblea;
                $fechaHoraAsamblea = $momento;
                break;
            }
        }

        if (!$asambleaProxima) {
            return response()->json([
                'success' => true,
                'tiene_asamblea' => false,
                'mensaje' => 'No hay asambleas pendientes'
            ]);
        }

        $horasRestantes = $ahora->diffInHours($fechaHoraAsamblea, false);

        // 2. Verificar si el vecino ya votó
        $asistencia = DB::table('asamblea_asistencias')
            ->where('asamblea_id', $asambleaProxima->id)
            ->where('user_id', $userId)
            ->first();

        $mostrarEncuesta = false;

        // CASO 1: Ventana inicial abierta (más de 24 horas)
        if ($horasRestantes > 24) {
            $yaNotificado = DB::table('zoe_asamblea_notificaciones_leidas')
                ->where('user_id', $userId)
                ->where('asamblea_id', $asambleaProxima->id)
                ->where('tipo', 'aviso_inicial')
                ->exists();

            if (!$yaNotificado && !$asistencia) {
                $this->inyectarMensajeZoe($userId, $asambleaProxima, 'encuesta');

                DB::table('zoe_asamblea_notificaciones_leidas')->insert([
                    'user_id' => $userId,
                    'asamblea_id' => $asambleaProxima->id,
                    'tipo' => 'aviso_inicial',
                    'created_at' => $ahora
                ]);

                $mostrarEncuesta = true;
            }
        }
        // CASO 2: Recordatorio de 24 horas antes
        elseif ($horasRestantes <= 24 && $horasRestantes > 0) {
            if ($asistencia && $asistencia->estado === 'no_asistira') {
                return response()->json(['success' => true, 'tiene_asamblea' => false]);
            }

            $yaNotificado24h = DB::table('zoe_asamblea_notificaciones_leidas')
                ->where('user_id', $userId)
                ->where('asamblea_id', $asambleaProxima->id)
                ->where('tipo', 'recordatorio_24h')
                ->exists();

            if (!$yaNotificado24h) {
                if ($asistencia && $asistencia->estado === 'asistira') {
                    $this->inyectarMensajeZoe($userId, $asambleaProxima, 'recordatorio_texto');
                } else {
                    $this->inyectarMensajeZoe($userId, $asambleaProxima, 'encuesta');
                    $mostrarEncuesta = true;
                }

                DB::table('zoe_asamblea_notificaciones_leidas')->insert([
                    'user_id' => $userId,
                    'asamblea_id' => $asambleaProxima->id,
                    'tipo' => 'recordatorio_24h',
                    'created_at' => $ahora
                ]);
            }
        }

        $fechaFormateada = Carbon::parse($asambleaProxima->fecha)->format('d/m/Y');
        $horaFormateada = Carbon::parse($asambleaProxima->hora)->format('h:i A');

        return response()->json([
            'success' => true,
            'tiene_asamblea' => $mostrarEncuesta,
            'asamblea_id' => $mostrarEncuesta ? $asambleaProxima->id : null,
            'texto' => $mostrarEncuesta ? (
                "Estimados vecinos, les recordamos que tendremos nuestra próxima asamblea vecinal.\n\n"
                . "📌 Asunto: {$asambleaProxima->titulo}\n"
                . "📅 Fecha: {$fechaFormateada}\n"
                . "⏰ Hora: {$horaFormateada}\n"
                . "📍 Lugar: {$asambleaProxima->lugar}\n"
                . "📝 Detalle: {$asambleaProxima->descripcion}\n\n"
                . "¿Contaremos con su asistencia?"
            ) : null
        ]);
    }

    public function confirmarAsistencia(Request $request)
    {
        $request->validate([
            'asamblea_id' => 'required|integer|exists:asambleas,id',
            'opcion' => 'required|in:asistira,no_asistira,en_duda',
        ]);

        $userId = $request->user()->id;
        $asambleaId = $request->asamblea_id;
        $opcion = $request->opcion;

        DB::table('asamblea_asistencias')->updateOrInsert(
            ['asamblea_id' => $asambleaId, 'user_id' => $userId],
            ['estado' => $opcion, 'updated_at' => now()]
        );

        $mensajesZoe = [
            'asistira' => 'Excelente, tu asistencia ha sido confirmada.',
            'no_asistira' => 'Lamentamos no tener tu presencia en la asamblea.',
            'en_duda' => 'Comprendo. Ojalá puedas darte un tiempo para acompañarnos; tu opinión siempre suma.',
        ];

        // Insertar respuesta de Zoe usando las columnas reales
        $conversacionId = $this->obtenerConversacionId($userId);
        DB::table('zoe_mensajes')->insert([
            'conversacion_id' => $conversacionId,
            'rol' => 'assistant',
            'mensaje' => $mensajesZoe[$opcion],
            'created_at' => now()
        ]);

        $stats = DB::table('asamblea_asistencias')
            ->where('asamblea_id', $asambleaId)
            ->selectRaw("
                COUNT(*) FILTER (WHERE estado = 'asistira') as asistiran,
                COUNT(*) FILTER (WHERE estado = 'en_duda') as en_duda,
                COUNT(*) FILTER (WHERE estado = 'no_asistira') as no_asistiran
            ")
            ->first();

        return response()->json([
            'status' => 'success',
            'mensaje_zoe' => $mensajesZoe[$opcion],
            'estadisticas' => [
                'asistiran' => (int) ($stats->asistiran ?? 0),
                'en_duda' => (int) ($stats->en_duda ?? 0),
                'no_asistiran' => (int) ($stats->no_asistiran ?? 0),
            ]
        ]);
    }

    private function inyectarMensajeZoe($userId, $asamblea, $tipo)
    {
        $fechaFormateada = Carbon::parse($asamblea->fecha)->format('d/m/Y');
        $horaFormateada = Carbon::parse($asamblea->hora)->format('h:i A');
        $conversacionId = $this->obtenerConversacionId($userId);

        if ($tipo === 'encuesta') {
            $texto = "Estimados vecinos, les recordamos que tendremos nuestra próxima asamblea vecinal.\n\n"
                   . "📌 Asunto: {$asamblea->titulo}\n"
                   . "📅 Fecha: {$fechaFormateada}\n"
                   . "⏰ Hora: {$horaFormateada}\n"
                   . "📍 Lugar: {$asamblea->lugar}\n"
                   . "📝 Detalle: {$asamblea->descripcion}\n\n"
                   . "¿Contaremos con su asistencia?\n"
                   . "[ASAMBLEA_ID:{$asamblea->id}]";

            DB::table('zoe_mensajes')->insert([
                'conversacion_id' => $conversacionId,
                'rol' => 'assistant',
                'mensaje' => $texto,
                'created_at' => now()
            ]);
        } elseif ($tipo === 'recordatorio_texto') {
            $texto = "Estimado vecino, le recordamos que mañana se llevará a cabo la asamblea sobre \"{$asamblea->titulo}\" a las {$horaFormateada} en {$asamblea->lugar}. Contamos con su puntual asistencia.";

            DB::table('zoe_mensajes')->insert([
                'conversacion_id' => $conversacionId,
                'rol' => 'assistant',
                'mensaje' => $texto,
                'created_at' => now()
            ]);
        }
    }
    public function obtenerEstadisticasActiva(Request $request)
{
    $userId = $request->user()->id;
    $ahora = Carbon::now();

    // 1. Buscar la asamblea publicada y próxima en la ventana de 5 días
    $asambleas = DB::table('asambleas')
        ->where('estado', 'publicada')
        ->whereDate('fecha', '>=', $ahora->toDateString())
        ->whereDate('fecha', '<=', $ahora->copy()->addDays(5)->toDateString())
        ->orderBy('fecha', 'asc')
        ->orderBy('hora', 'asc')
        ->get();

    $asamblea = null;
    foreach ($asambleas as $item) {
        $momento = Carbon::parse("{$item->fecha} {$item->hora}");
        if ($momento->greaterThan($ahora)) {
            $asamblea = $item;
            break;
        }
    }

    if (!$asamblea) {
        return response()->json([
            'tiene_asamblea' => false
        ]);
    }

    // 2. Conteo de votos de vecinos
    $stats = DB::table('asamblea_asistencias')
        ->where('asamblea_id', $asamblea->id)
        ->selectRaw("
            COUNT(*) FILTER (WHERE estado = 'asistira') as asistiran,
            COUNT(*) FILTER (WHERE estado = 'en_duda') as en_duda,
            COUNT(*) FILTER (WHERE estado = 'no_asistira') as no_asistiran,
            COUNT(*) as total
        ")
        ->first();

    // 3. Voto actual del vecino autenticado
    $miVoto = DB::table('asamblea_asistencias')
        ->where('asamblea_id', $asamblea->id)
        ->where('user_id', $userId)
        ->value('estado');

    return response()->json([
        'tiene_asamblea' => true,
        'asamblea_id' => $asamblea->id,
        'titulo' => $asamblea->titulo,
        'fecha' => Carbon::parse($asamblea->fecha)->format('d/m/Y'),
        'hora' => Carbon::parse($asamblea->hora)->format('h:i A'),
        'lugar' => $asamblea->lugar,
        'mi_voto' => $miVoto,
        'estadisticas' => [
            'asistiran' => (int) ($stats->asistiran ?? 0),
            'en_duda' => (int) ($stats->en_duda ?? 0),
            'no_asistiran' => (int) ($stats->no_asistiran ?? 0),
            'total' => (int) ($stats->total ?? 0),
        ]
    ]);
}
}