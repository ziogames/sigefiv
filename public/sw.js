const CACHE_NAME = 'sigefiv-v5';


/*
|--------------------------------------------------------------------------
| INSTALL
|--------------------------------------------------------------------------
*/

self.addEventListener('install', event => {

    console.log(
        '[SIGEFIV] Service Worker instalado:',
        CACHE_NAME
    );

    self.skipWaiting();

});


/*
|--------------------------------------------------------------------------
| ACTIVATE
|--------------------------------------------------------------------------
*/

self.addEventListener('activate', event => {

    console.log(
        '[SIGEFIV] Service Worker activo:',
        CACHE_NAME
    );

    event.waitUntil(

        self.clients.claim()

    );

});


/*
|--------------------------------------------------------------------------
| PUSH
|--------------------------------------------------------------------------
*/

self.addEventListener('push', event => {

    console.log(
        '[SIGEFIV] Push recibido'
    );


    /*
    |--------------------------------------------------------------------------
    | DATOS POR DEFECTO
    |--------------------------------------------------------------------------
    */

    let datos = {

        title:
            'Grupo Residencial 21',

        body:
            'Tienes una nueva notificación.',

        url:
            '/dashboard',

        icon:
            '/assets/asambleas/logo-grupo.png',

        badge:
            '/assets/pwa/icon-192.png',

        image:
            null,

        tag:
            'sigefiv-notificacion',

        tipo:
            'general',

        asamblea_id:
            null

    };


    /*
    |--------------------------------------------------------------------------
    | LEER PAYLOAD
    |--------------------------------------------------------------------------
    */

    if (event.data) {

        try {

            const payload =
                event.data.json();


            console.log(
                '[SIGEFIV] Payload:',
                payload
            );


            datos = {

                ...datos,

                ...payload

            };


        } catch (error) {

            console.error(
                '[SIGEFIV] Error leyendo payload:',
                error
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CONSTRUIR URL
    |--------------------------------------------------------------------------
    */

    let urlNotificacion =
        datos.url;


    /*
    |--------------------------------------------------------------------------
    | ASAMBLEA
    |--------------------------------------------------------------------------
    |
    | Para una asamblea NO confiamos en la URL recibida.
    | Construimos directamente la ruta pública.
    |
    */

    if (

        datos.tipo === 'asamblea' &&

        datos.asamblea_id

    ) {

        urlNotificacion =
            `/asambleas/${datos.asamblea_id}/citacion`;

    }


    console.log(
        '[SIGEFIV] URL final:',
        urlNotificacion
    );


    /*
    |--------------------------------------------------------------------------
    | OPCIONES
    |--------------------------------------------------------------------------
    */

    const opciones = {

        body:
            datos.body,

        icon:
            datos.icon,

        badge:
            datos.badge,

        tag:
            datos.tag,

        renotify:
            true,

        silent:
            false,

        vibrate: [

            200,
            100,
            200

        ],

        requireInteraction:
            false,


        /*
        |--------------------------------------------------------------------------
        | DATOS DE LA NOTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        data: {

            url:
                urlNotificacion,

            tipo:
                datos.tipo,

            asamblea_id:
                datos.asamblea_id

        },


        /*
        |--------------------------------------------------------------------------
        | BOTONES
        |--------------------------------------------------------------------------
        */

        actions: [

            {

                action:
                    'ver_citacion',

                title:
                    'VER CITACIÓN'

            },

            {

                action:
                    'cerrar',

                title:
                    'CERRAR'

            }

        ]

    };


    /*
    |--------------------------------------------------------------------------
    | IMAGEN
    |--------------------------------------------------------------------------
    */

    if (datos.image) {

        opciones.image =
            datos.image;

    }


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR
    |--------------------------------------------------------------------------
    */

    event.waitUntil(

        self.registration.showNotification(

            datos.title,

            opciones

        )

    );

});


/*
|--------------------------------------------------------------------------
| CLICK EN NOTIFICACIÓN
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'notificationclick',
    event => {

        console.log(
            '[SIGEFIV] CLICK NOTIFICACIÓN'
        );


        console.log(
            '[SIGEFIV] Acción:',
            event.action
        );


        console.log(
            '[SIGEFIV] Datos:',
            event.notification.data
        );


        /*
        |--------------------------------------------------------------------------
        | CERRAR
        |--------------------------------------------------------------------------
        */

        if (
            event.action === 'cerrar'
        ) {

            event.notification.close();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER DATOS
        |--------------------------------------------------------------------------
        */

        const datos =
            event.notification.data || {};


        /*
        |--------------------------------------------------------------------------
        | CONSTRUIR URL
        |--------------------------------------------------------------------------
        */

        let url;


        if (

            datos.tipo === 'asamblea' &&

            datos.asamblea_id

        ) {

            url =
                `/asambleas/${datos.asamblea_id}/citacion`;

        } else {

            url =
                datos.url ||
                '/dashboard';

        }


        /*
        |--------------------------------------------------------------------------
        | URL ABSOLUTA
        |--------------------------------------------------------------------------
        */

        const urlFinal =
            new URL(
                url,
                self.location.origin
            ).href;


        console.log(
            '[SIGEFIV] ABRIENDO:',
            urlFinal
        );


        /*
        |--------------------------------------------------------------------------
        | CERRAR NOTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        event.notification.close();


        /*
        |--------------------------------------------------------------------------
        | ABRIR DIRECTAMENTE
        |--------------------------------------------------------------------------
        |
        | No usamos client.navigate().
        |
        | Abrimos directamente la página.
        |
        */

        event.waitUntil(

            self.clients.openWindow(
                urlFinal
            )

        );

    }
);


/*
|--------------------------------------------------------------------------
| FETCH
|--------------------------------------------------------------------------
*/

self.addEventListener(
    'fetch',
    event => {

        // No cacheamos páginas dinámicas.

    }
);