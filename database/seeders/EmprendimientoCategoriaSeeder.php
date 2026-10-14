<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmprendimientoCategoria;
use Illuminate\Support\Str;

class EmprendimientoCategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Gasfitería y Electricidad',
            'Repostería y Comida',
            'Servicio Técnico',
            'Costura y Confección',
            'Clases Particulares e Informática',
            'Bodega y Abarrotes',
            'Belleza y Cuidado Personal',
            'Mascotas y Veterinaria',
            'Artesanía y Manualidades',
            'Transporte y Mudanzas',
            'Jardinería y Paisajismo',
            'Fotografía y Video',
            'Decoración y Diseño de Interiores',
            'Música y Entretenimiento',
            'Salud y Bienestar',
            'Turismo y Aventura',
            'Servicios Profesionales',
            'Otros'
            
        ];

        foreach ($categorias as $cat) {
            EmprendimientoCategoria::firstOrCreate(
                ['slug' => Str::slug($cat)],
                ['name' => $cat]
            );
        }
    }
}