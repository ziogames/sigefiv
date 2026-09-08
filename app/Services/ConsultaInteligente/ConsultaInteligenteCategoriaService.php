<?php

namespace App\Services\ConsultaInteligente;

use App\Models\Categoria;

class ConsultaInteligenteCategoriaService
{

        public function detectarCategorias(
            string $texto
        ): array {

            $categorias =
                Categoria::query()
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->get([
                        'id',
                        'nombre',
                        'tipo',
                    ]);

            $normalizar = function (string $valor): string {

                $valor = mb_strtolower(trim($valor));
                $valor = strtr($valor, [
                    'á' => 'a', 'é' => 'e', 'í' => 'i',
                    'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
                    'ñ' => 'n',
                ]);

                $valor = preg_replace('/[^a-z0-9\s]+/u', ' ', $valor);

                return trim(preg_replace('/\s+/u', ' ', $valor));
            };

            $textoNormalizado = $normalizar($texto);
            $palabrasTexto = array_values(array_filter(
                preg_split('/\s+/u', $textoNormalizado),
                fn ($palabra) => mb_strlen($palabra) >= 3
            ));

            $ignoradas = [
                'muestre', 'muéstrame', 'muestrame', 'mostrar', 'dame',
                'lista', 'listado', 'cuanto', 'cuánto', 'cuantos', 'cuántos',
                'cuantas', 'cuántas', 'que', 'qué', 'cual', 'cuál',
                'ingreso', 'ingresos', 'ingresamos', 'egreso', 'egresos',
                'gasto', 'gastos', 'gastamos', 'movimiento', 'movimientos',
                'por', 'para', 'del', 'los', 'las', 'una', 'unos', 'unas',
                'prof', 'profe', 'profesor', 'profesora', 'pago', 'pagos',
                'servicio', 'servicios', 'enero', 'febrero', 'marzo', 'abril',
                'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'setiembre',
                'octubre', 'noviembre', 'diciembre',
            ];

            $palabrasBusqueda = array_values(array_filter(
                $palabrasTexto,
                fn ($palabra) => !in_array($palabra, $ignoradas, true)
                    && !preg_match('/^20\d{2}$/', $palabra)
            ));

            $coincidencias = [];

            foreach ($categorias as $categoria) {

                $nombre = $normalizar((string) $categoria->nombre);

                if ($nombre === '') {
                    continue;
                }

                $palabrasCategoria = array_values(array_filter(
                    preg_split('/\s+/u', $nombre),
                    fn ($palabra) => mb_strlen($palabra) >= 3
                        && !in_array($palabra, $ignoradas, true)
                ));

                $patron = '/(?<![a-z0-9])' . preg_quote($nombre, '/') . '(?![a-z0-9])/u';

                if (preg_match($patron, $textoNormalizado, $match, PREG_OFFSET_CAPTURE)) {
                    $coincidencias[] = [
                        'categoria' => [
                            'id' => $categoria->id,
                            'nombre' => $categoria->nombre,
                            'tipo' => $categoria->tipo,
                        ],
                        'puntaje' => 10000 + count($palabrasCategoria) * 100,
                        'posicion' => $match[0][1],
                        'longitud' => mb_strlen($nombre),
                    ];
                    continue;
                }

                $coincidentes = [];

                foreach ($palabrasCategoria as $palabraCategoria) {
                    foreach ($palabrasBusqueda as $palabraBusqueda) {

                        if ($palabraCategoria === $palabraBusqueda) {
                            $coincidentes[] = $palabraCategoria;
                            break;
                        }

                        if (mb_strlen($palabraCategoria) >= 6 && mb_strlen($palabraBusqueda) >= 6) {
                            $distancia = levenshtein($palabraCategoria, $palabraBusqueda);
                            $maximo = max(mb_strlen($palabraCategoria), mb_strlen($palabraBusqueda));

                            if ($distancia <= 2 && $distancia <= floor($maximo / 3)) {
                                $coincidentes[] = $palabraCategoria;
                                break;
                            }
                        }
                    }
                }

                if (empty($coincidentes)) {
                    continue;
                }

                $cantidad = count(array_unique($coincidentes));
                $total = max(1, count($palabrasCategoria));
                if ($cantidad === 1 && mb_strlen($coincidentes[0]) < 6) {
                    continue;
                }
                $puntaje =
                    ($cantidad * 1000) +
                    (($cantidad / $total) * 500) +
                    (mb_strlen($nombre) * 2);
                $posicion = mb_strpos($textoNormalizado, $coincidentes[0]);
                $coincidencias[] = [
                    'categoria' => [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'tipo' => $categoria->tipo,
                    ],
                    'puntaje' => $puntaje,
                    'posicion' => $posicion === false ? PHP_INT_MAX : $posicion,
                    'longitud' => mb_strlen($nombre),
                ];
            }
            usort($coincidencias, function ($a, $b) {
                if ($a['puntaje'] !== $b['puntaje']) {
                    return $b['puntaje'] <=> $a['puntaje'];
                }
                if ($a['posicion'] !== $b['posicion']) {
                    return $a['posicion'] <=> $b['posicion'];
                }
                return $b['longitud'] <=> $a['longitud'];
            });
            $resultado = [];
            $ids = [];
            foreach ($coincidencias as $coincidencia) {
                $id = $coincidencia['categoria']['id'];
                if (in_array($id, $ids, true)) {
                    continue;
                }
                $ids[] = $id;
                $resultado[] = $coincidencia['categoria'];
            }
            return $resultado;
        }


        private function detectarCategoria(
            string $texto
        ): ?array {

            return $this->detectarCategorias($texto)[0] ?? null;
        }
}
