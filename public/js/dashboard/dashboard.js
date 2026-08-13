
/*
|--------------------------------------------------------------------------
| SIGEFIV Dashboard
|--------------------------------------------------------------------------
*/

let chartPrincipal = null;
let chartPie = null;
let chartSaldo = null;

document.addEventListener("DOMContentLoaded", () => {

    iniciarDashboard();

});

function iniciarDashboard() {

    iniciarGraficos();

    registrarEventos();

    iniciarReloj();

    actualizarDashboard();

}
/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function registrarEventos() {

    const anio = document.getElementById("anioDashboard");
    const mes = document.getElementById("mesDashboard");

    if (anio) {

        anio.addEventListener("change", actualizarDashboard);

    }

    if (mes) {

        mes.addEventListener("change", actualizarDashboard);

    }

}

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

async function actualizarDashboard() {

    const anio = document.getElementById("anioDashboard").value;

    const mes = document.getElementById("mesDashboard").value;

    try {

        const respuesta = await fetch(

            `/dashboard/datos?anio=${anio}&mes=${mes}`

        );

        if (!respuesta.ok) {

            throw new Error("No fue posible obtener los datos.");

        }

        const datos = await respuesta.json();

        actualizarKPIs(datos.kpis);

        actualizarIndicadores(datos);

        actualizarAlertas(datos);   // <-- AQUÍ

        actualizarInteligencia(datos);

        actualizarGraficoPrincipal(datos.grafico);

        actualizarGraficoPie(datos.pie);

        actualizarGraficoSaldo(datos.grafico);

        actualizarMovimientos(datos.movimientos);

    } catch (error) {

        console.error(error);

    }

}

/*
|--------------------------------------------------------------------------
| Crear gráficos
|--------------------------------------------------------------------------
*/

function iniciarGraficos() {

    crearGraficoPrincipal();

    crearGraficoPie();

    crearGraficoSaldo();

}

/*
|--------------------------------------------------------------------------
| Reloj
|--------------------------------------------------------------------------
*/

function iniciarReloj() {

    const reloj = document.getElementById("reloj");

    if (!reloj) return;

    setInterval(() => {

        reloj.textContent = new Date()

            .toLocaleTimeString();

    },1000);

}

/*
|--------------------------------------------------------------------------
| Gráfico Principal
|--------------------------------------------------------------------------
*/

function crearGraficoPrincipal() {

    const canvas = document.getElementById("graficoPrincipal");

    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    const datos = window.dashboard.grafico ?? [];

    /*
    |--------------------------------------------------------------------------
    | Degradados
    |--------------------------------------------------------------------------
    */

    const gradienteIngresos = ctx.createLinearGradient(0,0,0,350);

    gradienteIngresos.addColorStop(0,"#42d392");

    gradienteIngresos.addColorStop(1,"#198754");

    const gradienteEgresos = ctx.createLinearGradient(0,0,0,350);

    gradienteEgresos.addColorStop(0,"#ff7676");

    gradienteEgresos.addColorStop(1,"#dc3545");

    chartPrincipal = new Chart(ctx,{

        type:"bar",

        data:{

            labels:datos.map(x=>x.mes),

            datasets:[

                {

                    label:"Ingresos",

                    data:datos.map(x=>x.ingresos),

                    backgroundColor:gradienteIngresos,

                    borderRadius:10,

                    borderSkipped:false,

                    maxBarThickness:40

                },

                {

                    label:"Egresos",

                    data:datos.map(x=>x.egresos),

                    backgroundColor:gradienteEgresos,

                    borderRadius:10,

                    borderSkipped:false,

                    maxBarThickness:40

                }

            ]

        },

        options:{

            responsive:true,

            maintainAspectRatio:false,

            animation:{

                duration:1200,

                easing:"easeOutQuart"

            },

            plugins:{

                legend:{

                    position:"top",

                    labels:{

                        color:"#c7d0d9",

                        usePointStyle:true,

                        pointStyle:"circle"

                    }

                },

                tooltip:{

                    backgroundColor:"#20232a",

                    titleColor:"#ffffff",

                    bodyColor:"#ffffff",

                    padding:12,

                    cornerRadius:10,

                    callbacks:{

                        label:function(context){

                            return window.dashboard.simbolo+

                            " "+context.raw.toLocaleString("es-PE",{

                                minimumFractionDigits:2

                            });

                        }

                    }

                }

            },

            scales:{

                x:{

                    grid:{

                        display:false

                    },

                    ticks:{

                        color:"#c7d0d9"

                    }

                },

                y:{

                    beginAtZero:true,

                    grid:{

                        color:"rgba(255,255,255,.06)"

                    },

                    ticks:{

                        color:"#c7d0d9",

                        callback:function(value){

                            return window.dashboard.simbolo+" "+value;

                        }

                    }

                }

            }

        }

    });

}

/*
|--------------------------------------------------------------------------
| Actualizar gráfico principal
|--------------------------------------------------------------------------
*/

function actualizarGraficoPrincipal(datos) {

    if (!chartPrincipal) return;

    chartPrincipal.data.labels = datos.map(x => x.mes);

    chartPrincipal.data.datasets[0].data =

        datos.map(x => x.ingresos);

    chartPrincipal.data.datasets[1].data =

        datos.map(x => x.egresos);

    chartPrincipal.update();

}

/*
|--------------------------------------------------------------------------
| Gráfico Pie
|--------------------------------------------------------------------------
*/

function crearGraficoPie() {

    const canvas = document.getElementById("graficoPie");

    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    chartPie = new Chart(ctx, {

        type: "doughnut",

        data: {

            labels: [],

            datasets: [{

                data: [],

                backgroundColor: [

                    "#3b82f6",
                    "#22c55e",
                    "#f59e0b",
                    "#ef4444",
                    "#8b5cf6",
                    "#06b6d4",
                    "#ec4899",
                    "#84cc16",
                    "#f97316",
                    "#64748b"

                ],

                borderWidth: 2,

                borderColor: "#2b3035",

                hoverOffset: 15

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            cutout: "55%",

            animation: {

                animateRotate: true,

                animateScale: true,

                duration: 1200

            },

            plugins: {

                legend: {

                    position: "bottom",

                    labels: {

                        color: "#c7d0d9",

                        usePointStyle: true,

                        padding: 18,

                        font: {

                            size: 12

                        }

                    }

                },

                tooltip: {

                    backgroundColor: "#20232a",

                    titleColor: "#fff",

                    bodyColor: "#fff",

                    cornerRadius: 10,

                    callbacks: {

                        label(context) {

                            return context.label +

                                ": " +

                                window.dashboard.simbolo +

                                " " +

                                Number(context.raw).toLocaleString(

                                    "es-PE",

                                    {

                                        minimumFractionDigits: 2

                                    }

                                );

                        }

                    }

                }

            }

        }

    });

}

/*
|--------------------------------------------------------------------------
| Actualizar gráfico Pie
|--------------------------------------------------------------------------
*/

function actualizarGraficoPie(datos) {

    if (!chartPie) return;

    chartPie.data.labels = datos.map(x => x.nombre);

    chartPie.data.datasets[0].data =

        datos.map(x => x.total);

    chartPie.update();

}
/*
|--------------------------------------------------------------------------
| Gráfico Evolución del Saldo
|--------------------------------------------------------------------------
*/

function crearGraficoSaldo() {

    const canvas = document.getElementById("graficoSaldo");

    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    const datos = window.dashboard.grafico ?? [];

    /*
    |--------------------------------------------------------------------------
    | Degradado
    |--------------------------------------------------------------------------
    */

    const gradiente = ctx.createLinearGradient(0,0,0,350);

    gradiente.addColorStop(0,"rgba(13,110,253,.45)");

    gradiente.addColorStop(.4,"rgba(13,110,253,.18)");

    gradiente.addColorStop(1,"rgba(13,110,253,0)");

    chartSaldo = new Chart(ctx,{

        type:"line",

        data:{

            labels:datos.map(x=>x.mes),

            datasets:[{

                label:"Saldo",

                data:datos.map(x=>x.saldo),

                borderColor:"#0d6efd",

                backgroundColor:gradiente,

                fill:true,

                borderWidth:3,

                tension:.35,

                pointRadius:5,

                pointHoverRadius:8,

                pointBackgroundColor:"#0d6efd",

                pointBorderColor:"#ffffff",

                pointBorderWidth:2

            }]

        },

        options:{

            responsive:true,

            maintainAspectRatio:false,

            animation:{

                duration:1400,

                easing:"easeOutQuart"

            },

            interaction:{

                intersect:false,

                mode:"index"

            },

            plugins:{

                legend:{

                    display:false

                },

                tooltip:{

                    backgroundColor:"#20232a",

                    titleColor:"#fff",

                    bodyColor:"#fff",

                    cornerRadius:10,

                    callbacks:{

                        label(context){

                            return "Saldo: " +

                            window.dashboard.simbolo +

                            " " +

                            Number(context.raw).toLocaleString(

                                "es-PE",

                                {

                                    minimumFractionDigits:2

                                }

                            );

                        }

                    }

                }

            },

            scales:{

                x:{

                    grid:{

                        display:false

                    },

                    ticks:{

                        color:"#c7d0d9"

                    }

                },

                y:{

                    beginAtZero:true,

                    grid:{

                        color:"rgba(255,255,255,.06)"

                    },

                    ticks:{

                        color:"#c7d0d9",

                        callback:function(value){

                            return window.dashboard.simbolo+" "+value;

                        }

                    }

                }

            }

        }

    });

}

/*
|--------------------------------------------------------------------------
| Actualizar gráfico saldo
|--------------------------------------------------------------------------
*/

function actualizarGraficoSaldo(datos){

    if(!chartSaldo) return;

    chartSaldo.data.labels =

        datos.map(x=>x.mes);

    chartSaldo.data.datasets[0].data =

        datos.map(x=>x.saldo);

    chartSaldo.update();

}
/*
|--------------------------------------------------------------------------
| Actualizar KPIs
|--------------------------------------------------------------------------
*/

function actualizarKPIs(kpis) {

    const simbolo = window.dashboard.simbolo;

    const saldo = document.getElementById("saldoCaja");
    const ingresos = document.getElementById("ingresos");
    const egresos = document.getElementById("egresos");

    if (saldo) {

        animarNumero(

    "saldoCaja",

    kpis.saldoCaja,

    window.dashboard.simbolo + " "

);

animarNumero(

    "ingresos",

    kpis.ingresos,

    window.dashboard.simbolo + " "

);

animarNumero(

    "egresos",

    kpis.egresos,

    window.dashboard.simbolo + " "

);

    }

    if (ingresos) {

        ingresos.textContent =
            simbolo + " " + formatearMoneda(kpis.ingresos);

    }

    if (egresos) {

        egresos.textContent =
            simbolo + " " + formatearMoneda(kpis.egresos);

    }

}

/*
|--------------------------------------------------------------------------
| Últimos movimientos
|--------------------------------------------------------------------------
*/

function actualizarMovimientos(movimientos) {

    const tbody = document.getElementById("tablaMovimientos");

    if (!tbody) return;

    tbody.innerHTML = "";

    if (movimientos.length === 0) {

        tbody.innerHTML = `

            <tr>

                <td colspan="5"
                    class="text-center text-muted">

                    No existen movimientos.

                </td>

            </tr>

        `;

        return;

    }

    movimientos.forEach(movimiento => {

        tbody.innerHTML += `

            <tr>

                <td>${movimiento.fecha}</td>

                <td>${movimiento.concepto}</td>

                <td>${movimiento.categoria}</td>

                <td>

                    <span class="badge bg-${
                        movimiento.tipo === "Ingreso"
                            ? "success"
                            : "danger"
                    }">

                        ${movimiento.tipo}

                    </span>

                </td>

                <td class="text-end">

                    ${window.dashboard.simbolo}
                    ${formatearMoneda(movimiento.monto)}

                </td>

            </tr>

        `;

    });

}

/*
|--------------------------------------------------------------------------
| Utilidades
|--------------------------------------------------------------------------
*/

function formatearMoneda(valor) {

    return Number(valor).toLocaleString(

        "es-PE",

        {

            minimumFractionDigits: 2,

            maximumFractionDigits: 2

        }

    );

}

/*
|--------------------------------------------------------------------------
| Recarga automática cada 5 minutos
|--------------------------------------------------------------------------
*/

setInterval(() => {

    actualizarDashboard();

}, 300000);

/*
|--------------------------------------------------------------------------
| Dashboard listo
|--------------------------------------------------------------------------
*/

console.log(

    "SIGEFIV Dashboard cargado correctamente."

);
/*agregado dos funciones neuvas */
/*
|--------------------------------------------------------------------------
| Indicadores Inteligentes
|--------------------------------------------------------------------------
*/

function actualizarIndicadores(datos) {

    const ingresos = Number(datos.kpis.ingresos);
    const egresos = Number(datos.kpis.egresos);
    const saldo = Number(datos.kpis.saldoCaja);

    /*
    |---------------------------------------------------------
    | Liquidez
    |---------------------------------------------------------
    */

    let liquidez = 0;

    if (ingresos > 0) {

        liquidez = (saldo / ingresos) * 100;

    }

    document.getElementById("indicadorLiquidez").innerHTML =
        liquidez.toFixed(1) + "%";

    /*
    |---------------------------------------------------------
    | Rentabilidad
    |---------------------------------------------------------
    */

    let rentabilidad = 0;

    if (ingresos > 0) {

        rentabilidad =
            ((ingresos - egresos) / ingresos) * 100;

    }

    document.getElementById("indicadorRentabilidad").innerHTML =
        rentabilidad.toFixed(1) + "%";

    /*
    |---------------------------------------------------------
    | Estado financiero
    |---------------------------------------------------------
    */

    let estado = "Excelente";

    if (rentabilidad < 50)
        estado = "Bueno";

    if (rentabilidad < 20)
        estado = "Regular";

    if (rentabilidad < 0)
        estado = "Crítico";

    document.getElementById("estadoFinanciero").innerHTML =
        estado;

    /*
    |---------------------------------------------------------
    | Alertas
    |---------------------------------------------------------
    */

    let alertas = 0;

    if (egresos > ingresos)
        alertas++;

    if (saldo <= 0)
        alertas++;

   const cantidadAlertas =
    document.getElementById("cantidadAlertas");

if (cantidadAlertas) {

    cantidadAlertas.innerHTML = alertas;

}

}

/*
|--------------------------------------------------------------------------
| Centro de Alertas
|--------------------------------------------------------------------------
*/

function actualizarAlertas(datos){

    const panel = document.getElementById("panelAlertas");

    if(!panel) return;

    panel.innerHTML="";

    const ingresos = Number(datos.kpis.ingresos);

    const egresos = Number(datos.kpis.egresos);

    const saldo = Number(datos.kpis.saldoCaja);

    /*
    |--------------------------------------------------------------------------
    | Sin movimientos
    |--------------------------------------------------------------------------
    */

    if(ingresos===0 && egresos===0){

        panel.innerHTML += `

        <div class="alert alert-secondary">

            No existen movimientos para este período.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Ingresos mayores
    |--------------------------------------------------------------------------
    */

    if(ingresos>egresos){

        panel.innerHTML += `

        <div class="alert alert-success">

            <i class="fas fa-circle-check me-2"></i>

            Los ingresos superan a los egresos.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Egresos mayores
    |--------------------------------------------------------------------------
    */

    if(egresos>ingresos){

        panel.innerHTML += `

        <div class="alert alert-danger">

            <i class="fas fa-triangle-exclamation me-2"></i>

            Los egresos superan a los ingresos.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Caja baja
    |--------------------------------------------------------------------------
    */

    if(saldo<100){

        panel.innerHTML += `

        <div class="alert alert-warning">

            <i class="fas fa-wallet me-2"></i>

            El saldo en caja es bajo.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Caja negativa
    |--------------------------------------------------------------------------
    */

    if(saldo<0){

        panel.innerHTML += `

        <div class="alert alert-dark">

            <i class="fas fa-skull-crossbones me-2"></i>

            La caja presenta saldo negativo.

        </div>

        `;

    }

}
/*
|--------------------------------------------------------------------------
| Inteligencia Financiera
|--------------------------------------------------------------------------
*/

function actualizarInteligencia(datos){

    const panel=document.getElementById("panelInteligencia");

    if(!panel) return;

    panel.innerHTML="";

    const ingresos=Number(datos.kpis.ingresos);

    const egresos=Number(datos.kpis.egresos);

    const saldo=Number(datos.kpis.saldoCaja);

    let html="";

    /*
    |--------------------------------------------------------------------------
    | Flujo de caja
    |--------------------------------------------------------------------------
    */

    if(ingresos>egresos){

        html+=`

        <div class="alert alert-success">

            <strong>✔ Flujo de caja positivo</strong>

            <br>

            Los ingresos cubren completamente los egresos.

        </div>

        `;

    }

    if(egresos>ingresos){

        html+=`

        <div class="alert alert-danger">

            <strong>⚠ Déficit financiero</strong>

            <br>

            Los egresos son mayores que los ingresos.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Caja
    |--------------------------------------------------------------------------
    */

    if(saldo>5000){

        html+=`

        <div class="alert alert-primary">

            Excelente liquidez.

        </div>

        `;

    }
    else if(saldo>1000){

        html+=`

        <div class="alert alert-info">

            Liquidez adecuada.

        </div>

        `;

    }
    else{

        html+=`

        <div class="alert alert-warning">

            Conviene incrementar el saldo de caja.

        </div>

        `;

    }

    /*
    |--------------------------------------------------------------------------
    | Porcentaje de gastos
    |--------------------------------------------------------------------------
    */

    if(ingresos>0){

        const porcentaje=(egresos/ingresos)*100;

        html+=`

        <div class="alert alert-secondary">

            Los egresos representan

            <strong>${porcentaje.toFixed(1)}%</strong>

            de los ingresos.

        </div>

        `;

    }

    panel.innerHTML=html;

}
/*
|--------------------------------------------------------------------------
| Animar números
|--------------------------------------------------------------------------
*/

function animarNumero(id, destino, prefijo = "") {

    const elemento = document.getElementById(id);

    if (!elemento) return;

    let inicio = 0;

    const incremento = destino / 60;

    const animacion = setInterval(() => {

        inicio += incremento;

        if (inicio >= destino) {

            inicio = destino;

            clearInterval(animacion);

        }

        elemento.innerHTML =
            prefijo +
            Number(inicio).toLocaleString("es-PE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

    }, 15);

}