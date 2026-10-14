<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emprendimiento;
use App\Models\EmprendimientoCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmprendimientoController extends Controller
{
    // Listar categorías disponibles
// Listar categorías disponibles
    public function categorias()
    {
        // ✅ Ahora envía la lista directa, tal como Android la espera
        return response()->json(EmprendimientoCategoria::all());
    }

    // Listar emprendimientos del usuario autenticado
  public function index(Request $request)
{
    $emprendimientos = Emprendimiento::where('user_id', $request->user()->id)
        ->with(['photos', 'categoria'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($emprendimientos);
}

// Añade este método para cambiar el estado (published, paused, draft)
public function updateStatus(Request $request, $id)
{
    // 1. Buscamos el emprendimiento manualmente
    $emprendimiento = Emprendimiento::find($id);

    // 2. Verificamos que exista
    if (!$emprendimiento) {
        return response()->json(['message' => 'Emprendimiento no encontrado'], 404);
    }

    // 3. Verificamos que pertenezca al usuario autenticado
    if ((int)$emprendimiento->user_id !== (int)$request->user()->id) {
        return response()->json(['message' => 'No autorizado para modificar este negocio'], 403);
    }

    // 4. Validamos el nuevo estado
    $request->validate([
        'status' => 'required|in:draft,published,paused'
    ]);

    // 5. Guardamos y retornamos
    $emprendimiento->status = $request->status;
    $emprendimiento->save();

    return response()->json([
        'message' => 'Estado actualizado correctamente',
        'data' => $emprendimiento->load('photos', 'categoria')
    ]);
}

    // Crear un nuevo emprendimiento
public function store(Request $request)
    {
        $validated = $request->validate([
            // 👈 Cambiar 'categorias' por 'emprendimiento_categorias' aquí:
            'categoria_id' => 'required|exists:emprendimiento_categorias,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:50',
            'show_whatsapp' => 'boolean',
            'modality' => 'required|string',
            'attention_zone' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,paused',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        $emprendimiento = Emprendimiento::create($validated);

        return response()->json([
            'message' => 'Emprendimiento creado con éxito',
            'data' => $emprendimiento->load('photos', 'categoria')
        ], 201);
    }

    // Integración con ZOE para redactar descripciones
    public function generateWithZoe(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $name = $request->input('name');
        
        $description = "Ofrecemos servicios profesionales de {$name} con atención dedicada y de confianza para toda la comunidad vecinal. ¡Contáctanos y agenda tu servicio hoy mismo!";

        return response()->json(['description' => $description]);
    }

    


/**
 * Buscar emprendimientos publicados para ZOE,
 * ampliando coincidencias y ordenando por relevancia.
 */
public function buscarPublicados(Request $request)
{
    $request->validate([
        'q' => 'required|string|min:2|max:100',
        'limite' => 'nullable|integer|min:1|max:20',
        'pagina' => 'nullable|integer|min:1',
    ]);

    $termino = trim($request->input('q'));
    $limite = $request->integer('limite', 10);
    $pagina = $request->integer('pagina', 1);

    $normalizar = static function (string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i',
            'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'ñ' => 'n',
        ]);
    };

    $textoNormalizado = $normalizar($termino);

    // Quitar palabras que normalmente no aportan relevancia.
    $palabrasIgnoradas = [
        'busco', 'buscar', 'necesito', 'quiero', 'quisiera',
        'un', 'una', 'unos', 'unas', 'el', 'la', 'los', 'las',
        'de', 'del', 'para', 'por', 'con', 'en', 'que',
        'quien', 'donde', 'hay', 'algun', 'alguna',
    ];

    $palabras = preg_split(
        '/[^\p{L}\p{N}]+/u',
        $termino,
        -1,
        PREG_SPLIT_NO_EMPTY
    ) ?: [];

    $terminosBusqueda = [];

    foreach ($palabras as $palabra) {
        if (
            mb_strlen($palabra, 'UTF-8') >= 2
            && !in_array($normalizar($palabra), $palabrasIgnoradas, true)
        ) {
            $terminosBusqueda[] = $palabra;
        }
    }

    // Leer sinónimos configurados en PostgreSQL.
    $sinonimos = \Illuminate\Support\Facades\DB::table(
        'emprendimiento_busqueda_sinonimos'
    )->get(['termino', 'sinonimo', 'categoria_id']);

    $categoriasBusqueda = [];

    foreach ($sinonimos as $fila) {
        $palabra = $normalizar($fila->termino);

        if (
            $palabra !== ''
            && preg_match(
                '/(?<![\p{L}\p{N}])'
                . preg_quote($palabra, '/')
                . '(?![\p{L}\p{N}])/u',
                $textoNormalizado
            )
        ) {
            $terminosBusqueda[] = $fila->termino;
            $terminosBusqueda[] = $fila->sinonimo;

            if ($fila->categoria_id !== null) {
                $categoriasBusqueda[] = (int) $fila->categoria_id;
            }
        }
    }

    $terminosBusqueda = array_values(array_unique(
        array_filter(
            $terminosBusqueda,
            fn ($valor) => is_string($valor) && trim($valor) !== ''
        )
    ));

    $categoriasBusqueda = array_values(array_unique($categoriasBusqueda));

    // Evitar consultas vacías si el texto solo contiene palabras ignoradas.
    if (empty($terminosBusqueda) && empty($categoriasBusqueda)) {
        return response()->json([
            'success' => true,
            'total' => 0,
            'pagina' => $pagina,
            'por_pagina' => $limite,
            'ultima_pagina' => 1,
            'emprendimientos' => [],
        ]);
    }

    $patrones = array_map(
        fn ($palabra) => '%' . trim($palabra) . '%',
        $terminosBusqueda
    );

    // Puntuación: nombre = 100, categoría = 50, descripción = 20.
    
    $condicionesNombre = [];
    $condicionesCategoria = [];
    $condicionesDescripcion = [];

    foreach ($patrones as $patron) {
        $condicionesNombre[] = 'name ILIKE ?';

        $condicionesCategoria[] =
            'EXISTS (
                SELECT 1
                FROM emprendimiento_categorias ec
                WHERE ec.id = emprendimientos.categoria_id
                  AND ec.name ILIKE ?
            )';

        $condicionesDescripcion[] = 'description ILIKE ?';
    }

    $partesPuntuacion = [];
    $bindingsPuntuacion = [];

    if (!empty($patrones)) {
        $partesPuntuacion[] =
            '(CASE WHEN (' . implode(' OR ', $condicionesNombre) .
            ') THEN 100 ELSE 0 END)';

        array_push($bindingsPuntuacion, ...$patrones);

        $partesPuntuacion[] =
            '(CASE WHEN (' . implode(' OR ', $condicionesCategoria) .
            ') THEN 50 ELSE 0 END)';

        array_push($bindingsPuntuacion, ...$patrones);

        $partesPuntuacion[] =
            '(CASE WHEN (' . implode(' OR ', $condicionesDescripcion) .
            ') THEN 5 ELSE 0 END)';

        array_push($bindingsPuntuacion, ...$patrones);
    }

    $consulta = Emprendimiento::query()
        ->with('categoria')
        ->where('status', 'published');

    if (!empty($partesPuntuacion)) {
        $consulta->select('emprendimientos.*')
            ->selectRaw(
                implode(' + ', $partesPuntuacion) . ' AS relevancia',
                $bindingsPuntuacion
            );
    }

    $consulta->where(function ($query) use (
        $terminosBusqueda,
        $categoriasBusqueda
    ) {
        foreach ($terminosBusqueda as $palabra) {
            $query->orWhere('name', 'ILIKE', '%' . $palabra . '%')
                ->orWhere('description', 'ILIKE', '%' . $palabra . '%')
                ->orWhereHas('categoria', function ($categoria) use ($palabra) {
                    $categoria->where('name', 'ILIKE', '%' . $palabra . '%');
                });
        }

        if (!empty($categoriasBusqueda)) {
            $query->orWhereIn('categoria_id', $categoriasBusqueda);
        }
    });

    $total = (clone $consulta)->count();

    $consulta->when(
        !empty($partesPuntuacion),
        fn ($q) => $q->orderByDesc('relevancia')
    );

    $emprendimientos = $consulta
        ->orderBy('name')
        ->paginate($limite, ['*'], 'pagina', $pagina);

    $resultados = $emprendimientos->getCollection()
        ->map(function ($emprendimiento) {
            return [
                'id' => $emprendimiento->id,
                'nombre' => $emprendimiento->name,
                'descripcion' => $emprendimiento->description,
                'categoria' => $emprendimiento->categoria?->name,
                'telefono' => $emprendimiento->phone,
                'show_whatsapp' => (bool) $emprendimiento->show_whatsapp,
                'modalidad' => $emprendimiento->modality,
                'zona_atencion' => $emprendimiento->attention_zone,
                'horario' => $emprendimiento->schedule,
            ];
        })
        ->values();

    return response()->json([
        'success' => true,
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $limite,
        'ultima_pagina' => $emprendimientos->lastPage(),
        'emprendimientos' => $resultados,
    ]);
}




}