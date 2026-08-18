<?php

namespace App\Http\Controllers;

use App\Models\Asamblea;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsambleaController extends Controller
{
    /**
     * Mostrar listado de asambleas.
     */
    public function index()
    {
        $asambleas = Asamblea::with('creador')
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->paginate(10);

        return view(
            'asambleas.index',
            compact('asambleas')
        );
    }


    /**
     * Mostrar formulario para crear una asamblea.
     */
    public function create()
    {
        return view('asambleas.create');
    }


    /**
     * Guardar una nueva asamblea.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([

            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'convoca' => [
                'required',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'grupo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manzana' => [
                'nullable',
                'string',
                'max:50',
            ],

            'lote' => [
                'nullable',
                'string',
                'max:50',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'hora' => [
                'nullable',
                'date_format:H:i',
            ],

            'primera_citacion' => [
                'required',
                'date_format:H:i',
            ],

            /*
             * La segunda citación es opcional.
             * Si se informa, debe ser posterior a la primera.
             */
            'segunda_citacion' => [
                'nullable',
                'date_format:H:i',
                'after:primera_citacion',
            ],

            'lugar' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'importancia' => [
                'required',
                'in:normal,importante,urgente',
            ],

            'agenda' => [
                'nullable',
                'array',
            ],

            'agenda.*' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'plantilla_citacion' => [
                'required',
                'integer',
                'between:1,7',
            ],

        ]);


        $agenda = $datos['agenda'] ?? [];

        unset($datos['agenda']);


        /*
         * Toda asamblea nueva comienza como borrador
         * y todavía no tiene ninguna alerta enviada.
         */
        $datos['estado'] = 'borrador';

        $datos['created_by'] = auth()->id();

        $datos['alerta_enviada'] = false;

        $datos['alerta_enviada_at'] = null;


        DB::transaction(function () use (
            $datos,
            $agenda,
            &$asamblea
        ) {

            $asamblea = Asamblea::create($datos);


            $numero = 1;


            foreach ($agenda as $punto) {

                $punto = trim($punto);


                if ($punto === '') {
                    continue;
                }


                $asamblea->agendas()->create([
                    'numero' => $numero,
                    'descripcion' => $punto,
                ]);


                $numero++;
            }
        });


        return redirect()
            ->route('asambleas.show', $asamblea)
            ->with(
                'success',
                'Asamblea creada correctamente.'
            );
    }


    /**
     * Mostrar citaciones en formato para impresión A4.
     */
    public function imprimir(Asamblea $asamblea)
    {
        $asamblea->load([
            'creador',
            'agendas',
        ]);

        return view(
            'asambleas.imprimir',
            compact('asamblea')
        );
    }


    /**
     * Mostrar una asamblea.
     */
    public function show(Asamblea $asamblea)
    {
        $asamblea->load([
            'creador',
            'agendas',
        ]);

        return view(
            'asambleas.show',
            compact('asamblea')
        );
    }


    /**
     * Mostrar formulario para editar una asamblea.
     */
    public function edit(Asamblea $asamblea)
    {
        $asamblea->load('agendas');

        return view(
            'asambleas.edit',
            compact('asamblea')
        );
    }


    /**
     * Actualizar una asamblea.
     */
    public function update(
        Request $request,
        Asamblea $asamblea
    ) {

        $datos = $request->validate([

            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'convoca' => [
                'required',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'grupo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manzana' => [
                'nullable',
                'string',
                'max:50',
            ],

            'lote' => [
                'nullable',
                'string',
                'max:50',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'hora' => [
                'nullable',
                'date_format:H:i',
            ],

            'primera_citacion' => [
                'required',
                'date_format:H:i',
            ],

            /*
             * Segunda citación opcional.
             */
            'segunda_citacion' => [
                'nullable',
                'date_format:H:i',
                'after:primera_citacion',
            ],

            'lugar' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'importancia' => [
                'required',
                'in:normal,importante,urgente',
            ],

            'estado' => [
                'required',
                'in:borrador,publicada,cancelada',
            ],

            'agenda' => [
                'nullable',
                'array',
            ],

            'agenda.*' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'plantilla_citacion' => [
                'required',
                'integer',
                'between:1,7',
            ],

        ]);


        $agenda = $datos['agenda'] ?? [];

        unset($datos['agenda']);


        /*
         * Si la asamblea ya fue enviada, NO permitimos
         * alterar el estado de envío desde el formulario.
         */
        if ($asamblea->alerta_enviada) {

            $datos['alerta_enviada'] = true;

            $datos['alerta_enviada_at'] =
                $asamblea->alerta_enviada_at;

            $datos['estado'] = 'publicada';
        }


        DB::transaction(function () use (
            $asamblea,
            $datos,
            $agenda
        ) {

            $asamblea->update($datos);


            /*
             * Reemplazar agenda.
             */
            $asamblea->agendas()->delete();


            $numero = 1;


            foreach ($agenda as $punto) {

                $punto = trim($punto);


                if ($punto === '') {
                    continue;
                }


                $asamblea->agendas()->create([
                    'numero' => $numero,
                    'descripcion' => $punto,
                ]);


                $numero++;
            }
        });


        return redirect()
            ->route('asambleas.show', $asamblea)
            ->with(
                'success',
                'Asamblea actualizada correctamente.'
            );
    }


    /**
     * Enviar la convocatoria de la asamblea mediante Push.
     */
    public function enviar(
        Asamblea $asamblea,
        PushNotificationService $pushService
    ) {

        /*
        |--------------------------------------------------------------------------
        | PROTECCIÓN CONTRA DOBLE ENVÍO
        |--------------------------------------------------------------------------
        */

        if ($asamblea->alerta_enviada) {

            return redirect()
                ->route('asambleas.show', $asamblea)
                ->with(
                    'error',
                    'La alerta de esta asamblea ya fue enviada anteriormente.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | CARGAR AGENDA
        |--------------------------------------------------------------------------
        */

        $asamblea->load('agendas');


        /*
        |--------------------------------------------------------------------------
        | FECHA EN ESPAÑOL
        |--------------------------------------------------------------------------
        */

        $dias = [
            'Sunday' => 'domingo',
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábado',
        ];


        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];


        $diaSemana =
            $dias[
                $asamblea->fecha->format('l')
            ];


        $dia =
            $asamblea->fecha->format('d');


        $mes =
            $meses[
                (int) $asamblea->fecha->format('n')
            ];


        $anio =
            $asamblea->fecha->format('Y');


        $fechaTexto =
            "{$diaSemana} {$dia} de {$mes} de {$anio}";


        /*
        |--------------------------------------------------------------------------
        | HORAS
        |--------------------------------------------------------------------------
        */

        $primeraCitacion = null;

        if ($asamblea->primera_citacion) {

            $primeraCitacion =
                $asamblea->primera_citacion
                    ->format('h:i') . ' p. m.';
        }


        $segundaCitacion = null;

        if ($asamblea->segunda_citacion) {

            $segundaCitacion =
                $asamblea->segunda_citacion
                    ->format('h:i') . ' p. m.';
        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO DE LA NOTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        $titulo = 'CITACIÓN VECINAL';


        /*
        |--------------------------------------------------------------------------
        | MENSAJE
        |--------------------------------------------------------------------------
        |
        | Este será el texto visible inicialmente en la
        | notificación de Android.
        |
        */

        $mensaje =
            $asamblea->titulo;


        if ($asamblea->descripcion) {

            $descripcion =
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        strip_tags(
                            $asamblea->descripcion
                        )
                    )
                );


            if ($descripcion !== '') {

                $mensaje .=
                    "\n" .
                    $descripcion;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN ADICIONAL
        |--------------------------------------------------------------------------
        */

        $datosExtra = [

            'tipo' => 'asamblea',

            'tag' => 'asamblea-' . $asamblea->id,

            'asamblea_id' => $asamblea->id,

            'fecha' => $fechaTexto,

            'primera_citacion' =>
                $primeraCitacion,

            'segunda_citacion' =>
                $segundaCitacion,

            'lugar' =>
                $asamblea->lugar,

            'convoca' =>
                $asamblea->convoca,

            'plantilla_citacion' =>
                (int) $asamblea->plantilla_citacion,

            /*
             * Logo de Grupo Residencial 21.
             */
            'icon' =>
                '/assets/asambleas/logo-grupo.png',

            /*
             * Badge pequeño para Android.
             */
            'badge' =>
                '/assets/pwa/icon-192.png',
        ];


        /*
        |--------------------------------------------------------------------------
        | FONDO DE LA CITACIÓN
        |--------------------------------------------------------------------------
        |
        | El fondo seleccionado también puede utilizarse
        | como imagen grande de la notificación cuando
        | Android soporte la presentación expandida.
        |
        */

        $plantilla =
            (int) (
                $asamblea->plantilla_citacion ?? 1
            );


        if (
            $plantilla < 1 ||
            $plantilla > 7
        ) {

            $plantilla = 1;

        }


        $datosExtra['image'] =
            url(
                "/assets/asambleas/fondos/fondo-{$plantilla}.jpg"
            );


        /*
        |--------------------------------------------------------------------------
        | URL DIRECTA A LA CITACIÓN
        |--------------------------------------------------------------------------
        */

        $url = route(
            'asambleas.citacion',
            $asamblea
        );


        $enviadas = 0;


        /*
        |--------------------------------------------------------------------------
        | SUSCRIPCIONES
        |--------------------------------------------------------------------------
        */

        $suscripciones =
            \App\Models\PushSubscription::query()
                ->get();


        /*
        |--------------------------------------------------------------------------
        | ENVIAR A TODOS LOS DISPOSITIVOS
        |--------------------------------------------------------------------------
        */

        foreach ($suscripciones as $suscripcion) {

            if (
                $pushService->enviar(
                    $suscripcion,
                    $titulo,
                    $mensaje,
                    $url,
                    $datosExtra
                )
            ) {

                $enviadas++;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | MARCAR COMO ENVIADA
        |--------------------------------------------------------------------------
        |
        | Solo se marca como enviada después de terminar
        | el proceso de envío.
        |
        */

        $asamblea->update([

            'estado' =>
                'publicada',

            'alerta_enviada' =>
                true,

            'alerta_enviada_at' =>
                now(),

        ]);


        return redirect()
            ->route(
                'asambleas.show',
                $asamblea
            )
            ->with(
                'success',
                "Convocatoria enviada correctamente a {$enviadas} dispositivo(s)."
            );
    }


    /**
     * Mostrar la citación pública para los vecinos.
     */
    public function citacion(Asamblea $asamblea)
    {
        $asamblea->load([
            'creador',
            'agendas',
        ]);

        return view(
            'asambleas.citacion',
            compact('asamblea')
        );
    }


    /**
     * Eliminar una asamblea.
     */
    public function destroy(Asamblea $asamblea)
    {
        $asamblea->delete();

        return redirect()
            ->route('asambleas.index')
            ->with(
                'success',
                'Asamblea eliminada correctamente.'
            );
    }
}