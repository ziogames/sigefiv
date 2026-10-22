<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cargo;

class CargoSeeder extends Seeder
{
    public function run(): void
    {
        $cargos = [
            [
                'nombre' => 'Secretaría General',
                'descripcion' => 'Representación legal y dirección general de la Junta Directiva Central del Grupo Residencial 21.'
            ],
            [
                'nombre' => 'Subsecretaría General',
                'descripcion' => 'Apoyo directo a la Secretaría General y reemplazo en funciones según estatutos.'
            ],
            [
                'nombre' => 'Secretaría de Servicios y Desarrollo Urbano',
                'descripcion' => 'Gestión de obras, servicios y mejoras urbanas en el sector.'
            ],
            [
                'nombre' => 'Secretaría de Economía',
                'descripcion' => 'Administración de fondos, contabilidad, control de ingresos y egresos de SIGEFIV.'
            ],
            [
                'nombre' => 'Secretaría de Medio Ambiente',
                'descripcion' => 'Cuidado de áreas verdes, limpieza y salubridad ambiental del residencial.'
            ],
            [
                'nombre' => 'Secretaría de Vigilancia',
                'descripcion' => 'Control de seguridad ciudadana, accesos y resguardo de la comunidad.'
            ],
            [
                'nombre' => 'Secretaría de Educación y Cultura',
                'descripcion' => 'Promoción de actividades culturales, educativas y de integración vecinal.'
            ],
            [
                'nombre' => 'Secretaría de Salud',
                'descripcion' => 'Coordinación de campañas de salud y bienestar para las familias residentes.'
            ],
            [
                'nombre' => 'Secretaría de Deportes',
                'descripcion' => 'Organización de eventos deportivos, campeonatos y uso de lozas deportivas.'
            ],
            // ... los cargos de la directiva ...
    [
        'nombre' => 'Vecino',
        'descripcion' => 'Cuenta asignada de solo lectura para residentes del Grupo Residencial 21.'
    ],
        ];

        foreach ($cargos as $cargo) {
            Cargo::firstOrCreate(
                ['nombre' => $cargo['nombre']], 
                ['descripcion' => $cargo['descripcion']]
            );
        }
    }
}