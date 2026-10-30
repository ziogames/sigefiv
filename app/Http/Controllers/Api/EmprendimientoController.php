<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emprendimiento;
use App\Models\EmprendimientoFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmprendimientoController extends Controller
{
    // Listar emprendimientos del usuario autenticado
    public function index(Request $request)
    {
        $emprendimientos = Emprendimiento::where('user_id', $request->user()->id)
            ->with(['photos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($emprendimientos);
    }

    // Cambiar el estado (published, paused, draft)
    public function updateStatus(Request $request, $id)
    {
        $emprendimiento = Emprendimiento::find($id);

        if (!$emprendimiento) {
            return response()->json(['message' => 'Emprendimiento no encontrado'], 404);
        }

        if ((int)$emprendimiento->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado para modificar este negocio'], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,published,paused'
        ]);

        $emprendimiento->status = $request->status;
        $emprendimiento->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data' => $emprendimiento->load('photos')
        ]);
    }

    // Crear un nuevo emprendimiento (con soporte para fotos)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'keywords' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:50',
            'show_whatsapp' => 'boolean',
            'modality' => 'required|string',
            'attention_zone' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,paused',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Validación de imágenes
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        // 1. Crear el emprendimiento
        $emprendimiento = Emprendimiento::create($validated);

        // 2. 📸 Procesar y guardar las fotos en la tabla emprendimiento_fotos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('emprendimientos', 'public');

                EmprendimientoFoto::create([
                    'emprendimiento_id' => $emprendimiento->id,
                    'path' => $path,
                    'is_primary' => $index === 0 // La primera foto se marca como principal
                ]);
            }
        }

        // 3. Notificar a n8n para generar sinónimos (si está publicado)
        if ($emprendimiento->status === 'published') {
            try {
                $webhookUrl = config('services.n8n.webhook_sinonimos');
                if ($webhookUrl) {
                    Http::timeout(3)->post($webhookUrl, [
                        'emprendimiento_id' => $emprendimiento->id,
                        'nombre' => $emprendimiento->name,
                        'keywords' => $emprendimiento->keywords,
                        'descripcion' => $emprendimiento->description
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error al contactar a n8n para sinónimos: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Emprendimiento creado con éxito',
            'data' => $emprendimiento->load('photos')
        ], 201);
    }

    // Actualizar un emprendimiento existente (con soporte para fotos)
    public function update(Request $request, $id)
    {
        $emprendimiento = Emprendimiento::find($id);

        if (!$emprendimiento) {
            return response()->json(['message' => 'Emprendimiento no encontrado'], 404);
        }

        if ((int)$emprendimiento->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado para modificar este negocio'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'keywords' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:50',
            'show_whatsapp' => 'boolean',
            'modality' => 'required|string',
            'attention_zone' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,paused',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $emprendimiento->update($validated);

        // 📸 Procesar nuevas fotos si se envían en la actualización
        if ($request->hasFile('photos')) {
            // Opcional: Si deseas reemplazar todas las fotos anteriores al actualizar, descomenta la siguiente línea:
            // foreach ($emprendimiento->photos as $fotoAntigua) { Storage::disk('public')->delete($fotoAntigua->path); } $emprendimiento->photos()->delete();

            $tieneFotosPrevias = $emprendimiento->photos()->count() > 0;

            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('emprendimientos', 'public');

                EmprendimientoFoto::create([
                    'emprendimiento_id' => $emprendimiento->id,
                    'path' => $path,
                    'is_primary' => (!$tieneFotosPrevias && $index === 0)
                ]);
            }
        }

        if ($emprendimiento->status === 'published') {
            try {
                $webhookUrl = config('services.n8n.webhook_sinonimos');
                if ($webhookUrl) {
                    Http::timeout(3)->post($webhookUrl, [
                        'emprendimiento_id' => $emprendimiento->id,
                        'nombre' => $emprendimiento->name,
                        'keywords' => $emprendimiento->keywords,
                        'descripcion' => $emprendimiento->description
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error al contactar a n8n para sinónimos (update): ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Emprendimiento actualizado con éxito',
            'data' => $emprendimiento->load('photos')
        ]);
    }

    // Integración con ZOE para redactar descripciones
    public function generateWithZoe(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $name = $request->input('name');
        $draftDescription = $request->input('description', '');

        try {
            $webhookUrl = config('services.n8n.webhook_descripcion');

            $response = Http::timeout(10)->post($webhookUrl, [
                'name' => $name,
                'description' => $draftDescription
            ]);

            if ($response->successful() && $response->json('description')) {
                $generatedText = trim($response->json('description'));
                $generatedText = trim($generatedText, '"');
                
                return response()->json(['description' => $generatedText]);
            }
        } catch (\Exception $e) {
            Log::error('Error al contactar n8n para ZOE descripción: ' . $e->getMessage());
        }

        $fallback = "Bienvenidos a {$name}. Ofrecemos productos y servicios de calidad pensados para el bienestar de toda la comunidad. ¡Contáctanos y visítanos!";
        return response()->json(['description' => $fallback]);
    }
// Buscar emprendimientos publicados de forma segura y estricta usando keywords, descripción y categorías
    public function buscarPublicados(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'categoria' => 'nullable|string|in:Comida,Servicios,Belleza,Construcción,Comercio,Todos',
            'limite' => 'nullable|integer|min:1|max:20',
            'pagina' => 'nullable|integer|min:1',
        ]);

        $termino = trim(ltrim($request->input('q'), '='));
        $categoriaFiltro = trim((string) $request->input('categoria'));
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

        // Lista estricta de palabras conversacionales y pronombres
        $palabrasIgnoradas = [
            'hola', 'zoe', 'buenos', 'dias', 'tardes', 'noches', 'por', 'favor', 'porfa',
            'busco', 'buscar', 'necesito', 'quiero', 'quisiera', 'recomiendame', 'recomiendas',
            'conoces', 'conocer', 'sabes', 'saben', 'saber', 'alguien', 'algun', 'alguna',
            'vecino', 'vecina', 'vecinos', 'vecinas', 'comunidad', 'grupo', 'sigefiv',
            'vende', 'venden', 'venda', 'vendan', 'venta', 'comprar', 'compro', 'ofrece', 'ofrecen',
            'hace', 'hacen', 'haga', 'prepara', 'arregla', 'arreglan', 'arreglando', 'repara', 'reparan',
            'ayuda', 'ayude', 'ayudar', 'servicio', 'servicios', 'comida', 'comidas', 'comercio',
            'tienda', 'negocio', 'emprendimiento', 'emprendimientos',
            'un', 'una', 'unos', 'unas', 'el', 'la', 'los', 'las',
            'de', 'del', 'para', 'por', 'con', 'en', 'que', 'quien', 'quienes', 
            'donde', 'dond', 'don', 'puedo', 'puede', 'pueden', 'podria', 'hay',
            'opcion', 'opciones', 'disponible', 'disponibles', 'todos'
        ];

        $palabras = preg_split('/[^\p{L}\p{N}]+/u', $termino, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terminosBusqueda = [];

        foreach ($palabras as $palabra) {
            $limpia = $normalizar($palabra);
            if (mb_strlen($limpia, 'UTF-8') >= 3 && !in_array($limpia, $palabrasIgnoradas, true)) {
                $terminosBusqueda[] = $limpia;
            }
        }

        $terminosBusqueda = array_values(array_unique($terminosBusqueda));
        $tieneCategoriaValida = !empty($categoriaFiltro) && $categoriaFiltro !== 'Todos';

        if (empty($terminosBusqueda) && !$tieneCategoriaValida) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'pagina' => $pagina,
                'por_pagina' => $limite,
                'ultima_pagina' => 1,
                'emprendimientos' => [],
            ]);
        }

        $consulta = Emprendimiento::query()->where('status', 'published');

        // 🎯 1. Si viene una categoría clara (ej. Comercio), restringir por ese rubro
        if ($tieneCategoriaValida) {
            $consulta->where('categoria', $categoriaFiltro);
        }

        // 🎯 2. Filtrar por coincidencia de términos en Nombre, Keywords o Descripción
        if (!empty($terminosBusqueda)) {
            $consulta->where(function ($query) use ($terminosBusqueda) {
                foreach ($terminosBusqueda as $palabra) {
                    $raiz = mb_strlen($palabra, 'UTF-8') >= 5
                        ? mb_substr($palabra, 0, -1, 'UTF-8')
                        : $palabra;

                    $query->orWhereRaw('unaccent(lower(name)) ILIKE ?', ['%' . $palabra . '%'])
                          ->orWhereRaw('unaccent(lower(name)) ILIKE ?', ['%' . $raiz . '%'])
                          ->orWhereRaw('unaccent(lower(COALESCE(keywords, \'\'))) ILIKE ?', ['%' . $palabra . '%'])
                          ->orWhereRaw('unaccent(lower(COALESCE(keywords, \'\'))) ILIKE ?', ['%' . $raiz . '%'])
                          ->orWhereRaw('unaccent(lower(COALESCE(description, \'\'))) ILIKE ?', ['%' . $palabra . '%'])
                          ->orWhereRaw('unaccent(lower(COALESCE(description, \'\'))) ILIKE ?', ['%' . $raiz . '%']);
                }
            });
        }

        $total = (clone $consulta)->count();

        $relacionFotos = method_exists(Emprendimiento::class, 'photos') ? 'photos' : 'fotos';

        $emprendimientos = $consulta
            ->with($relacionFotos)
            ->orderBy('name')
            ->paginate($limite, ['*'], 'pagina', $pagina);

        $user = auth('sanctum')->user();

        $resultados = $emprendimientos->getCollection()
            ->map(function ($emprendimiento) use ($user, $relacionFotos) {
                $esFavorito = false;
                if ($user) {
                    $esFavorito = $user->emprendimientosFavoritos()
                        ->where('emprendimiento_id', $emprendimiento->id)
                        ->exists();
                }

                $coleccionFotos = $emprendimiento->$relacionFotos ?? collect();

                $fotosFormateadas = $coleccionFotos->map(function ($f) {
                    $pathLimpio = ltrim($f->path ?? $f->ruta ?? '', '/');
                    return [
                        'id' => $f->id,
                        'path' => $pathLimpio,
                        'url' => $pathLimpio,
                    ];
                })->values();

                $primeraFotoPath = $fotosFormateadas->first()['path'] ?? null;

                return [
                    'id' => $emprendimiento->id,
                    'name' => $emprendimiento->name,
                    'nombre' => $emprendimiento->name,
                    'categoria' => $emprendimiento->categoria ?? 'Servicios',
                    'description' => $emprendimiento->description,
                    'descripcion' => $emprendimiento->description,
                    'keywords' => $emprendimiento->keywords,
                    'phone' => $emprendimiento->phone,
                    'telefono' => $emprendimiento->phone,
                    'show_whatsapp' => (bool) $emprendimiento->show_whatsapp,
                    'modality' => $emprendimiento->modality,
                    'modalidad' => $emprendimiento->modality,
                    'attention_zone' => $emprendimiento->attention_zone,
                    'zona_atencion' => $emprendimiento->attention_zone,
                    'schedule' => $emprendimiento->schedule,
                    'horario' => $emprendimiento->schedule,
                    'es_favorito' => $esFavorito,
                    'imagen' => $primeraFotoPath,
                    'imagen_url' => $primeraFotoPath,
                    'photos' => $fotosFormateadas,
                    'fotos' => $fotosFormateadas,
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

    // Eliminar un emprendimiento del usuario autenticado
    public function destroy(Request $request, $id)
    {
        $emprendimiento = Emprendimiento::find($id);

        if (!$emprendimiento) {
            return response()->json(['message' => 'Emprendimiento no encontrado'], 404);
        }

        if ((int)$emprendimiento->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado para eliminar este negocio'], 403);
        }

        // Eliminar archivos físicos del almacenamiento
        foreach ($emprendimiento->photos as $foto) {
            Storage::disk('public')->delete($foto->path);
        }

        $emprendimiento->photos()->delete();
        $emprendimiento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emprendimiento eliminado correctamente'
        ]);
    }

    // Listar todos los emprendimientos publicados para el directorio general de la app
    // Listar todos los emprendimientos publicados para el directorio general de la app
    public function indexPublicos(Request $request)
    {
        $categoria = $request->input('categoria');
        $user = auth('sanctum')->user();

        $query = Emprendimiento::where('status', 'published')
            ->with(['photos'])
            ->withAvg('opiniones as promedio_calificacion', 'calificacion')
            ->withCount('opiniones as total_opiniones');

       if ($categoria && $categoria !== 'Todos') {
            $query->where('categoria', $categoria); // 👈 Filtro exacto por columna
        }

        $emprendimientos = $query->orderBy('created_at', 'desc')->get();

        $resultados = $emprendimientos->map(function ($emprendimiento) use ($user) {
            $esFavorito = false;
            if ($user) {
                $esFavorito = $user->emprendimientosFavoritos()->where('emprendimiento_id', $emprendimiento->id)->exists();
            }

            return [
                'id' => $emprendimiento->id,
                'name' => $emprendimiento->name,
                'categoria' => $emprendimiento->categoria ?? 'Servicios',
                'keywords' => $emprendimiento->keywords,
                'description' => $emprendimiento->description,
                'phone' => $emprendimiento->phone,
                'show_whatsapp' => (bool) $emprendimiento->show_whatsapp,
                'modality' => $emprendimiento->modality,
                'attention_zone' => $emprendimiento->attention_zone,
                'schedule' => $emprendimiento->schedule,
                'status' => $emprendimiento->status,
                'photos' => $emprendimiento->photos ?? [],
                'es_favorito' => $esFavorito,
                // 🌟 Promedio redondeado a 1 decimal y conteo total de opiniones
                'promedio_calificacion' => round((float) ($emprendimiento->promedio_calificacion ?? 0.0), 1),
                'total_opiniones' => (int) ($emprendimiento->total_opiniones ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'emprendimientos' => $resultados
        ]);
    }

    // Marcar o desmarcar favorito
    public function toggleFavorito(Request $request, $id)
    {
        $user = $request->user();
        $emprendimiento = Emprendimiento::find($id);

        if (!$emprendimiento) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $user->emprendimientosFavoritos()->toggle($id);

        $esFavorito = $user->emprendimientosFavoritos()->where('emprendimiento_id', $id)->exists();

        return response()->json([
            'success' => true,
            'es_favorito' => $esFavorito,
            'message' => $esFavorito ? 'Agregado a favoritos' : 'Eliminado de favoritos'
        ]);
    }

    // Listar favoritos del usuario
    public function misFavoritos(Request $request)
    {
        $user = $request->user();
        
        $favoritos = $user->emprendimientosFavoritos()->with(['photos'])->get();

        $resultados = $favoritos->map(function ($emprendimiento) {
            return [
                'id' => $emprendimiento->id,
                'name' => $emprendimiento->name,
                'categoria' => $emprendimiento->categoria ?? 'Servicios',
                'keywords' => $emprendimiento->keywords,
                'description' => $emprendimiento->description,
                'phone' => $emprendimiento->phone,
                'show_whatsapp' => (bool) $emprendimiento->show_whatsapp,
                'modality' => $emprendimiento->modality,
                'attention_zone' => $emprendimiento->attention_zone,
                'schedule' => $emprendimiento->schedule,
                'status' => $emprendimiento->status,
                'photos' => $emprendimiento->photos ?? [],
                'es_favorito' => true,
            ];
        });

        return response()->json([
            'success' => true,
            'emprendimientos' => $resultados
        ]);
    }
}