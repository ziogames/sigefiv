@php

    /*
     * =========================================================
     * DATOS PARA LOS GRÁFICOS
     * =========================================================
     */

  

    $totalIngresos = $movimientos
        ->where('tipo', 'Ingreso')
        ->sum('monto');

    $totalEgresos = $movimientos
        ->where('tipo', 'Egreso')
        ->sum('monto');


    /*
     * =========================================================
     * EGRESOS POR CATEGORÍA
     * =========================================================
     */

    $categoriasGrafico = $movimientos
        ->where('tipo', 'Egreso')
        ->groupBy(function ($movimiento) {

            return $movimiento->categoria->nombre;

        })
        ->map(function ($items) {

            return $items->sum('monto');

        });

@endphp
<style>
    .movimientos-tooltip {
        position: absolute;
        z-index: 1000;
        pointer-events: none;

        background: #111827;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 10px 14px;

        color: #f8fafc;
        font-size: 13px;

        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);

        white-space: nowrap;

        opacity: 0;
        transform: translateY(-50%);

        transition:
            opacity 0.12s ease,
            left 0.12s ease,
            top 0.12s ease;
    }

    .movimientos-tooltip .tooltip-titulo {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .movimientos-tooltip .tooltip-valor {
        color: #cbd5e1;
    }
</style>

<script>

    /*
     * =========================================================
     * INSTANCIAS DE CHART.JS
     * =========================================================
     *
     * Usamos window para evitar errores si este archivo
     * se carga más de una vez.
     */

    window.sigefivCharts =
        window.sigefivCharts || {};

    window.sigefivCharts.ingresosEgresos = null;

    window.sigefivCharts.categorias = null;

    window.sigefivCharts.tipos = null;


    document.addEventListener(
        'DOMContentLoaded',
        function () {


            /*
             * =================================================
             * DATOS GENERALES
             * =================================================
             */

          const cantidadIngresos =
    Number(@json($cantidadIngresos));

const cantidadEgresos =
    Number(@json($cantidadEgresos));


            const totalIngresos =
                @json((float) $totalIngresos);


            const totalEgresos =
                @json((float) $totalEgresos);


            /*
             * =================================================
             * DATOS MENSUALES
             * =================================================
             */

            const graficoMensualLabels =
                @json($graficoMensualLabels ?? []);


            const graficoMensualIngresos =
                @json($graficoMensualIngresos ?? []);


            const graficoMensualEgresos =
                @json($graficoMensualEgresos ?? []);


            /*
             * =================================================
             * GRÁFICO 1
             *
             * INGRESOS VS EGRESOS POR MES
             * =================================================
             */

            const canvasIngresosEgresos =
                document.getElementById(
                    'graficoIngresosEgresos'
                );


            if (canvasIngresosEgresos) {


                /*
                 * Destruir gráfico anterior
                 */

                if (
                    window.sigefivCharts.ingresosEgresos
                ) {

                    window.sigefivCharts.ingresosEgresos.destroy();

                    window.sigefivCharts.ingresosEgresos =
                        null;

                }


                /*
                 * =================================================
                 * DATOS MENSUALES
                 * =================================================
                 */

                if (
                    graficoMensualLabels.length > 0
                ) {

                    window.sigefivCharts.ingresosEgresos =
                        new Chart(
                            canvasIngresosEgresos,
                            {

                                type: 'bar',

                                data: {

                                    labels:
                                        graficoMensualLabels,

                                   datasets: (() => {

    const datasets = [];

    const tipoSeleccionado =
        @json($tipo ?? '');


    /*
     * =====================================================
     * INGRESOS
     * =====================================================
     */

    if (
        tipoSeleccionado === '' ||
        tipoSeleccionado === 'Ingreso'
    ) {

        datasets.push({

            label: 'Ingresos',

            data: graficoMensualIngresos,

            backgroundColor:
                'rgba(34, 197, 94, 0.75)',

            borderColor:
                '#22c55e',

            borderWidth: 1,

            borderRadius: 5

        });

    }


    /*
     * =====================================================
     * EGRESOS
     * =====================================================
     */

    if (
        tipoSeleccionado === '' ||
        tipoSeleccionado === 'Egreso'
    ) {

        datasets.push({

            label: 'Egresos',

            data: graficoMensualEgresos,

            backgroundColor:
                'rgba(239, 68, 68, 0.75)',

            borderColor:
                '#ef4444',

            borderWidth: 1,

            borderRadius: 5

        });

    }


    return datasets;

})(),

                                },


                                options: {

                                    responsive: true,

                                    maintainAspectRatio: false,


                                    plugins: {

                                        legend: {

                                            display: true,

                                            position: 'top',

                                            labels: {

                                                color:
                                                    '#cbd5e1',

                                                boxWidth: 12,

                                                padding: 12

                                            }

                                        },


                                        tooltip: {

                                            callbacks: {

                                                label:
                                                    function (
                                                        context
                                                    ) {

                                                        return (
                                                            ' ' +
                                                            context.dataset.label +
                                                            ': S/ ' +
                                                            Number(
                                                                context.raw
                                                            ).toLocaleString(
                                                                'es-PE',
                                                                {
                                                                    minimumFractionDigits: 2
                                                                }
                                                            )
                                                        );

                                                    }

                                            }

                                        }

                                    },


                                    scales: {

                                        x: {

                                            ticks: {

                                                color:
                                                    '#94a3b8',

                                                maxRotation: 45,

                                                minRotation: 0

                                            },

                                            grid: {

                                                display: false

                                            }

                                        },


                                        y: {

                                            beginAtZero: true,

                                            ticks: {

                                                color:
                                                    '#94a3b8',

                                                callback:
                                                    function (
                                                        value
                                                    ) {

                                                        return (
                                                            'S/ ' +
                                                            value
                                                        );

                                                    }

                                            },

                                            grid: {

                                                color:
                                                    'rgba(148,163,184,.08)'

                                            }

                                        }

                                    }

                                }

                            }
                        );

                }

                /*
                 * =================================================
                 * SIN DATOS MENSUALES
                 * =================================================
                 */

                else {

                    window.sigefivCharts.ingresosEgresos =
                        new Chart(
                            canvasIngresosEgresos,
                            {

                                type: 'bar',

                                data: {

                                    labels: [
                                        'Ingresos',
                                        'Egresos'
                                    ],

                                    datasets: [

                                        {

                                            data: [

                                                totalIngresos,

                                                totalEgresos

                                            ],

                                            backgroundColor: [

                                                'rgba(34, 197, 94, 0.75)',

                                                'rgba(239, 68, 68, 0.75)'

                                            ],

                                            borderColor: [

                                                '#22c55e',

                                                '#ef4444'

                                            ],

                                            borderWidth: 1,

                                            borderRadius: 6

                                        }

                                    ]

                                },


                                options: {

                                    responsive: true,

                                    maintainAspectRatio: false,


                                    plugins: {

                                        legend: {

                                            display: false

                                        },


                                        tooltip: {

                                            callbacks: {

                                                label:
                                                    function (
                                                        context
                                                    ) {

                                                        return (
                                                            ' S/ ' +
                                                            Number(
                                                                context.raw
                                                            ).toLocaleString(
                                                                'es-PE',
                                                                {
                                                                    minimumFractionDigits: 2
                                                                }
                                                            )
                                                        );

                                                    }

                                            }

                                        }

                                    },


                                    scales: {

                                        x: {

                                            ticks: {

                                                color:
                                                    '#94a3b8'

                                            },

                                            grid: {

                                                display: false

                                            }

                                        },


                                        y: {

                                            beginAtZero: true,

                                            ticks: {

                                                color:
                                                    '#94a3b8',

                                                callback:
                                                    function (
                                                        value
                                                    ) {

                                                        return (
                                                            'S/ ' +
                                                            value
                                                        );

                                                    }

                                            },

                                            grid: {

                                                color:
                                                    'rgba(148,163,184,.08)'

                                            }

                                        }

                                    }

                                }

                            }
                        );

                }

            }

                        /*
             * =================================================
             * GRÁFICO 2
             *
             * EGRESOS POR CATEGORÍA
             * =================================================
             */

           /*
 * =================================================
 * GRÁFICO 2
 *
 * CATEGORÍAS SEGÚN EL TIPO SELECCIONADO
 * =================================================
 */

const categorias =
    @json($categoriasGrafico);


const tipoSeleccionado =
    @json($tipo ?? '');


const canvasCategorias =
    document.getElementById(
        'graficoCategorias'
    );


if (
    canvasCategorias &&
    Object.keys(categorias).length > 0
) {


    /*
     * Destruir gráfico anterior
     */

    if (
        window.sigefivCharts.categorias
    ) {

        window.sigefivCharts.categorias.destroy();

        window.sigefivCharts.categorias =
            null;

    }


    /*
     * Título dinámico
     */

    let tituloCategorias =
        'Distribución por categoría';


    if (
        tipoSeleccionado === 'Ingreso'
    ) {

        tituloCategorias =
            'Ingresos por categoría';

    }


    if (
        tipoSeleccionado === 'Egreso'
    ) {

        tituloCategorias =
            'Egresos por categoría';

    }


    /*
     * Crear gráfico
     */

    window.sigefivCharts.categorias =
        new Chart(
            canvasCategorias,
            {

                type: 'doughnut',


                data: {

                    labels:
                        Object.keys(
                            categorias
                        ),


                    datasets: [

                        {

                            data:
                                Object.values(
                                    categorias
                                ),


                            backgroundColor: [

                                '#3b82f6',

                                '#22c55e',

                                '#f59e0b',

                                '#ef4444',

                                '#8b5cf6',

                                '#06b6d4',

                                '#ec4899',

                                '#14b8a6',

                                '#f97316',

                                '#84cc16'

                            ],


                            borderColor:
                                '#1c2330',


                            borderWidth: 3

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,


                    plugins: {

                        title: {

                            display: true,

                            text:
                                tituloCategorias,

                            color:
                                '#e2e8f0',

                            font: {

                                size: 15,

                                weight: '600'

                            },

                            padding: {

                                bottom: 15

                            }

                        },


                        legend: {

                            position: 'bottom',


                            labels: {

                                color:
                                    '#cbd5e1',

                                boxWidth: 12,

                                padding: 10

                            }

                        },


                        tooltip: {

                            callbacks: {

                                label:
                                    function (
                                        context
                                    ) {

                                        return (
                                            ' S/ ' +
                                            Number(
                                                context.raw
                                            ).toLocaleString(
                                                'es-PE',
                                                {
                                                    minimumFractionDigits: 2
                                                }
                                            )
                                        );

                                    }

                            }

                        }

                    }

                }

            }
        );

}


            if (
                canvasCategorias &&
                Object.keys(categorias).length > 0
            ) {


                /*
                 * Destruir gráfico anterior
                 */

                if (
                    window.sigefivCharts.categorias
                ) {

                    window.sigefivCharts.categorias.destroy();

                    window.sigefivCharts.categorias =
                        null;

                }


                /*
                 * Crear gráfico
                 */

                window.sigefivCharts.categorias =
                    new Chart(
                        canvasCategorias,
                        {

                            type: 'doughnut',


                            data: {

                                labels:
                                    Object.keys(categorias),


                                datasets: [

                                    {

                                        data:
                                            Object.values(
                                                categorias
                                            ),


                                        backgroundColor: [

                                            '#3b82f6',

                                            '#22c55e',

                                            '#f59e0b',

                                            '#ef4444',

                                            '#8b5cf6',

                                            '#06b6d4',

                                            '#ec4899',

                                            '#14b8a6'

                                        ],


                                        borderColor:
                                            '#1c2330',


                                        borderWidth: 3

                                    }

                                ]

                            },


                            options: {

                                responsive: true,

                                maintainAspectRatio: false,


                                plugins: {

                                    legend: {

                                        position: 'bottom',


                                        labels: {

                                            color:
                                                '#cbd5e1',

                                            boxWidth: 12,

                                            padding: 10

                                        }

                                    },


                                    tooltip: {

                                        callbacks: {

                                            label:
                                                function (
                                                    context
                                                ) {

                                                    return (
                                                        ' S/ ' +
                                                        Number(
                                                            context.raw
                                                        ).toLocaleString(
                                                            'es-PE',
                                                            {
                                                                minimumFractionDigits: 2
                                                            }
                                                        )
                                                    );

                                                }

                                        }

                                    }

                                }

                            }

                        }
                    );

            }


            /*
             * =================================================
             * GRÁFICO 3
             *
             * MOVIMIENTOS POR TIPO
             * =================================================
             */

            const canvasTipos =
                document.getElementById(
                    'graficoTipos'
                );


            if (canvasTipos) {


                /*
                 * Destruir gráfico anterior
                 */

                if (
                    window.sigefivCharts.tipos
                ) {

                    window.sigefivCharts.tipos.destroy();

                    window.sigefivCharts.tipos =
                        null;

                }


                /*
                 * Crear gráfico
                 */
                const pluginCentro =
    {
        id: 'textoCentroMovimientos',

        afterDraw: function (chart) {

            const {
                ctx,
                chartArea
            } = chart;

            const total =
                cantidadIngresos +
                cantidadEgresos;

            const centroX =
                (chartArea.left + chartArea.right) / 2;

            const centroY =
                (chartArea.top + chartArea.bottom) / 2;

            ctx.save();

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.fillStyle = '#f8fafc';

            ctx.font =
                'bold 24px Arial';

            ctx.fillText(
                total,
                centroX,
                centroY - 8
            );

            ctx.fillStyle = '#94a3b8';

            ctx.font =
                '12px Arial';

            ctx.fillText(
                'movimientos',
                centroX,
                centroY + 14
            );

            ctx.restore();
        }
    };



               window.sigefivCharts.tipos =
    new Chart(
        canvasTipos,
        {
            type: 'doughnut',

            plugins: [
                pluginCentro
            ],

            data: {
                                labels: [

                                    'Ingresos',

                                    'Egresos'

                                ],


                                datasets: [

                                    {

                                        data: [

                                            cantidadIngresos,

                                            cantidadEgresos

                                        ],


                                        backgroundColor: [

                                            '#22c55e',

                                            '#ef4444'

                                        ],


                                        borderColor:
                                            '#1c2330',


                                        borderWidth: 3

                                    }

                                ]

                            },


                           options: {

    responsive: true,

    maintainAspectRatio: false,

    plugins: {

        legend: {
            position: 'bottom',

            labels: {
                color: '#cbd5e1',
                boxWidth: 12,
                padding: 10
            }
        },

        tooltip: {

    enabled: false,

    external: function(context) {

        const chart = context.chart;
        const tooltip = context.tooltip;

        let tooltipEl =
            chart.canvas.parentNode.querySelector(
                '.movimientos-tooltip'
            );

        /*
         * Crear tooltip si no existe
         */
        if (!tooltipEl) {

            tooltipEl = document.createElement('div');

            tooltipEl.className =
                'movimientos-tooltip';

            chart.canvas.parentNode.appendChild(
                tooltipEl
            );
        }

        /*
         * Ocultar tooltip
         */
        if (tooltip.opacity === 0) {

            tooltipEl.style.opacity = 0;

            return;
        }

        /*
         * Datos
         */
        const dataPoint =
            tooltip.dataPoints[0];

        const label =
            dataPoint.label;

        const cantidad =
            dataPoint.raw;

        const total =
            cantidadIngresos +
            cantidadEgresos;

        tooltipEl.innerHTML = `
            <div class="tooltip-titulo">
                ${label}
            </div>

            <div class="tooltip-valor">
                ${cantidad} movimiento${cantidad !== 1 ? 's' : ''}
            </div>

            <div class="tooltip-valor">
                Total: ${total} movimientos
            </div>
        `;

        /*
         * Mostrar temporalmente para
         * poder conocer sus dimensiones
         */
        tooltipEl.style.opacity = 0;
        tooltipEl.style.left = '0px';
        tooltipEl.style.top = '0px';

        const tooltipWidth =
            tooltipEl.offsetWidth;

        const tooltipHeight =
            tooltipEl.offsetHeight;

        /*
         * Posición del gráfico
         */
        const position =
            chart.canvas.getBoundingClientRect();

        const parent =
            chart.canvas.parentNode.getBoundingClientRect();

        /*
         * Posición del cursor dentro
         * del contenedor
         */
        const x =
            tooltip.caretX +
            (position.left - parent.left);

        const y =
            tooltip.caretY +
            (position.top - parent.top);

        const espacioDerecha =
            parent.width - x;

        const espacioIzquierda =
            x;

        const separacion = 18;

        /*
         * Decidir automáticamente:
         *
         * derecha → si hay espacio
         * izquierda → si no hay espacio
         */
        let left;

        if (
            espacioDerecha >=
            tooltipWidth + separacion
        ) {

            left =
                x +
                separacion;

        } else {

            left =
                x -
                tooltipWidth -
                separacion;
        }

        /*
         * Evitar que salga del contenedor
         */
        left = Math.max(
            5,
            Math.min(
                left,
                parent.width -
                tooltipWidth -
                5
            )
        );

        /*
         * Posición vertical
         */
        let top =
            y -
            tooltipHeight / 2;

        top = Math.max(
            5,
            Math.min(
                top,
                parent.height -
                tooltipHeight -
                5
            )
        );

        /*
         * Aplicar posición
         */
        tooltipEl.style.left =
            `${left}px`;

        tooltipEl.style.top =
            `${top}px`;

        tooltipEl.style.opacity = 1;
    }

}

    }

}

                        }
                    );

            }
                    });
    

</script>