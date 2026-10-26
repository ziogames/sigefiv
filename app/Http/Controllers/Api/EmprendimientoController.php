<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emprendimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    // Crear un nuevo emprendimiento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'keywords' => 'required|string|max:255',
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

        // 1. Crear el emprendimiento
        $emprendimiento = Emprendimiento::create($validated);

        // 2. Notificar a n8n para generar sinónimos (si está publicado)
        if ($emprendimiento->status === 'published') {
            try {
                $webhookUrl = config('services.n8n.webhook_sinonimos');
                Http::timeout(3)->post($webhookUrl, [
                    'emprendimiento_id' => $emprendimiento->id,
                    'nombre' => $emprendimiento->name,
                    'keywords' => $emprendimiento->keywords,
                    'descripcion' => $emprendimiento->description
                ]);
            } catch (\Exception $e) {
                Log::error('Error al contactar a n8n para sinónimos: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Emprendimiento creado con éxito',
            'data' => $emprendimiento->load('photos')
        ], 201);
    }

    // Actualizar un emprendimiento existente
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
            'keywords' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:50',
            'show_whatsapp' => 'boolean',
            'modality' => 'required|string',
            'attention_zone' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,paused',
        ]);

        $emprendimiento->update($validated);

        // Notificar a n8n si se actualizó y está publicado (opcional)
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

    // Buscar emprendimientos publicados de forma segura y estricta usando keywords
    public function buscarPublicados(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limite' => 'nullable|integer|min:1|max:20',
            'pagina' => 'nullable|integer|min:1',
        ]);

        $termino = trim(ltrim($request->input('q'), '='));
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

        $palabrasIgnoradas = [
            'hola', 'zoe', 'buenos', 'dias', 'tardes', 'noches', 'por', 'favor', 'porfa',
            'busco', 'buscar', 'necesito', 'quiero', 'quisiera', 'recomiendame', 'recomiendas',
            'conoces', 'conocer', 'sabes', 'saben', 'saber', 'alguien', 'algun', 'alguna',
            'vecino', 'vecina', 'vecinos', 'vecinas', 'comunidad', 'grupo', 'sigefiv',
            'vende', 'venden', 'venda', 'vendan', 'venta', 'comprar', 'compro', 'ofrece', 'ofrecen',
            'hace', 'hacen', 'haga', 'prepara', 'arregla', 'repara', 'servicio', 'servicios',
            'tienda', 'negocio', 'emprendimiento', 'emprendimientos',
            'un', 'una', 'unos', 'unas', 'el', 'la', 'los', 'las',
            'de', 'del', 'para', 'por', 'con', 'en', 'que', 'quien', 'quienes', 'donde', 'hay',
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

        if (empty($terminosBusqueda)) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'pagina' => $pagina,
                'por_pagina' => $limite,
                'ultima_pagina' => 1,
                'emprendimientos' => [],
            ]);
        }

        // Búsqueda estricta apuntando exclusivamente al nombre y a las palabras clave (keywords)
        $consulta = Emprendimiento::query()
            ->where('status', 'published')
            ->where(function ($query) use ($terminosBusqueda) {
                foreach ($terminosBusqueda as $palabra) {
                    $raiz = mb_strlen($palabra, 'UTF-8') >= 5
                        ? mb_substr($palabra, 0, -1, 'UTF-8')
                        : $palabra;

                    $query->orWhereRaw('unaccent(lower(name)) ILIKE ?', ['%' . $raiz . '%'])
                          ->orWhereRaw('unaccent(lower(COALESCE(keywords, \'\'))) ILIKE ?', ['%' . $palabra . '%']);
                }
            });

        $total = (clone $consulta)->count();

        $emprendimientos = $consulta
            ->orderBy('name')
            ->paginate($limite, ['*'], 'pagina', $pagina);

        $resultados = $emprendimientos->getCollection()
            ->map(function ($emprendimiento) {
                return [
                    'id' => $emprendimiento->id,
                    'nombre' => $emprendimiento->name,
                    'descripcion' => $emprendimiento->description,
                    // Mostramos las palabras clave reales en lugar de la categoría eliminada
                    'categoria' => $emprendimiento->keywords,
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
    // Eliminar un emprendimiento del usuario autenticado
    public function destroy(Request $request, $id)
    {
        $emprendimiento = Emprendimiento::find($id);

        if (!$emprendimiento) {
            return response()->json(['message' => 'Emprendimiento no encontrado'], 404);
        }

        // Validación estricta de seguridad: solo el dueño puede eliminarlo
        if ((int)$emprendimiento->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado para eliminar este negocio'], 403);
        }

        // Eliminar fotos asociadas del almacenamiento si es necesario o dejar que cascada lo maneje
        $emprendimiento->photos()->delete();
        
        // Eliminar el emprendimiento
        $emprendimiento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emprendimiento eliminado correctamente'
        ]);
    }
}