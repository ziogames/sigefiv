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

            'segunda_citacion' => [
                'required',
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

        ]);


        $agenda = $datos['agenda'] ?? [];

        unset($datos['agenda']);


        $datos['estado'] = 'borrador';

        $datos['created_by'] = auth()->id();


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

            'segunda_citacion' => [
                'required',
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

        ]);


        $agenda = $datos['agenda'] ?? [];

        unset($datos['agenda']);


        DB::transaction(function () use (
            $asamblea,
            $datos,
            $agenda
        ) {

            $asamblea->update($datos);


            /*
            |--------------------------------------------------------------------------
            | Reemplazar agenda
            |--------------------------------------------------------------------------
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
    $asamblea->load('agendas');

    $titulo = '📢 CITACIÓN VECINAL';

    $mensaje = $asamblea->titulo;

    if ($asamblea->fecha) {
        $mensaje .= ' — ' . $asamblea->fecha->format('d/m/Y');
    }

    if ($asamblea->primera_citacion) {
        $mensaje .= ' — 1ra citación ' .
            $asamblea->primera_citacion->format('H:i');
    }

    $url = route(
        'asambleas.show',
        $asamblea
    );

    $enviadas = 0;

    $suscripciones = \App\Models\PushSubscription::query()
        ->get();

    foreach ($suscripciones as $suscripcion) {

        if (
            $pushService->enviar(
                $suscripcion,
                $titulo,
                $mensaje,
                $url
            )
        ) {
            $enviadas++;
        }
    }

    $asamblea->update([
        'estado' => 'publicada',
    ]);

    return redirect()
        ->route('asambleas.show', $asamblea)
        ->with(
            'success',
            "Convocatoria enviada correctamente a {$enviadas} dispositivo(s)."
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