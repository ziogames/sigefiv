const CACHE_NAME = 'sigefiv-v3';


/*
|--------------------------------------------------------------------------
| INSTALL
|--------------------------------------------------------------------------
*/

self.addEventListener('install', event => {

    console.log(
        '[SIGEFIV] Service Worker instalado'
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
        '[SIGEFIV] Service Worker activo'
    );

    event.waitUntil(

        self.clients.claim()

    );

});


/*
|--------------------------------------------------------------------------
| PUSH
|--------------------------------------------------------------------------
|
| Recibe la notificación enviada por Laravel/WebPush.
|
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

        title: 'Grupo Residencial 21',

        body: 'Tienes una nueva notificación.',

        url: '/dashboard',

        icon: '/assets/asambleas/logo-grupo.png',

        badge: '/assets/pwa/icon-192.png',

        image: null,

        tag: 'sigefiv-notificacion',

        tipo: 'general'

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


            datos = {

                ...datos,

                ...payload

            };


        } catch (error) {

            console.warn(

                '[SIGEFIV] No se pudo interpretar el payload Push.',

                error

            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | OPCIONES DE LA NOTIFICACIÓN
    |--------------------------------------------------------------------------
    */

    const opciones = {

        /*
        |--------------------------------------------------------------------------
        | CONTENIDO
        |--------------------------------------------------------------------------
        */

        body: datos.body,

        icon: datos.icon,

        badge: datos.badge,


        /*
        |--------------------------------------------------------------------------
        | IDENTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        tag: datos.tag,

        renotify: true,


        /*
        |--------------------------------------------------------------------------
        | SONIDO
        |--------------------------------------------------------------------------
        |
        | false significa que NO estamos silenciando la notificación.
        |
        | Android / navegador utilizará el comportamiento de sonido
        | configurado para las notificaciones.
        |
        */

        silent: false,


        /*
        |--------------------------------------------------------------------------
        | VIBRACIÓN
        |--------------------------------------------------------------------------
        */

        vibrate: [

            200,
            100,
            200

        ],


        /*
        |--------------------------------------------------------------------------
        | INTERACCIÓN
        |--------------------------------------------------------------------------
        */

        requireInteraction: false,


        /*
        |--------------------------------------------------------------------------
        | DATOS PARA EL CLICK
        |--------------------------------------------------------------------------
        */

        data: {

            url: datos.url,

            tipo: datos.tipo,

            asamblea_id:
                datos.asamblea_id ?? null

        },


        /*
        |--------------------------------------------------------------------------
        | BOTONES
        |--------------------------------------------------------------------------
        */

        actions: [

            {

                action: 'ver_citacion',

                title: 'VER CITACIÓN'

            },

            {

                action: 'cerrar',

                title: 'CERRAR'

            }

        ]

    };


    /*
    |--------------------------------------------------------------------------
    | IMAGEN GRANDE
    |--------------------------------------------------------------------------
    |
    | Se utiliza solamente cuando Laravel envía una imagen.
    |
    */

    if (datos.image) {

        opciones.image =
            datos.image;

    }


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR NOTIFICACIÓN
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
| CLICK EN LA NOTIFICACIÓN
|--------------------------------------------------------------------------
*/

self.addEventListener(

    'notificationclick',

    event => {

        console.log(

            '[SIGEFIV] Notificación seleccionada:',

            event.action

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
        | OBTENER URL
        |--------------------------------------------------------------------------
        */

        const url =

            event.notification.data?.url ||

            '/dashboard';


        /*
        |--------------------------------------------------------------------------
        | CERRAR NOTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        event.notification.close();


        /*
        |--------------------------------------------------------------------------
        | ABRIR / ENFOCAR SIGEFIV
        |--------------------------------------------------------------------------
        */

        event.waitUntil(

            self.clients
                .matchAll({

                    type: 'window',

                    includeUncontrolled: true

                })

                .then(clients => {


                    /*
                    |--------------------------------------------------------------------------
                    | SI SIGEFIV YA ESTÁ ABIERTO
                    |--------------------------------------------------------------------------
                    */

                    for (
                        const client of clients
                    ) {


                        if (
                            'focus' in client
                        ) {


                            return client
                                .navigate(url)
                                .then(() => {

                                    return client.focus();

                                });


                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SI SIGEFIV NO ESTÁ ABIERTO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        self.clients.openWindow
                    ) {

                        return self.clients.openWindow(

                            url

                        );

                    }

                })

        );

    }

);


/*
|--------------------------------------------------------------------------
| FETCH
|--------------------------------------------------------------------------
|
| Las peticiones pasan directamente a Laravel.
| No cacheamos datos financieros ni páginas dinámicas.
|
*/

self.addEventListener(

    'fetch',

    event => {

        // Sin caché para datos dinámicos.

    }

);