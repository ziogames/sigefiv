const CACHE_NAME = 'sigefiv-v4';


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

        tipo: 'general',

        asamblea_id: null

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
                '[SIGEFIV] Payload recibido:',
                payload
            );


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
    | CONSTRUIR URL
    |--------------------------------------------------------------------------
    |
    | Si es una asamblea y tenemos su ID,
    | construimos directamente la URL pública
    | de la citación.
    |
    */

    let urlNotificacion =
        datos.url;


    if (
        datos.tipo === 'asamblea' &&
        datos.asamblea_id
    ) {

        urlNotificacion =
            `/asambleas/${datos.asamblea_id}/citacion`;

    }


    console.log(
        '[SIGEFIV] URL de la notificación:',
        urlNotificacion
    );


    /*
    |--------------------------------------------------------------------------
    | OPCIONES
    |--------------------------------------------------------------------------
    */

    const opciones = {

        body: datos.body,

        icon: datos.icon,

        badge: datos.badge,

        tag: datos.tag,

        renotify: true,

        silent: false,

        vibrate: [

            200,
            100,
            200

        ],

        requireInteraction: false,


        /*
        |--------------------------------------------------------------------------
        | DATOS QUE QUEDAN GUARDADOS EN LA NOTIFICACIÓN
        |--------------------------------------------------------------------------
        */

        data: {

            url: urlNotificacion,

            tipo: datos.tipo,

            asamblea_id:
                datos.asamblea_id ?? null

        },


        /*
        |--------------------------------------------------------------------------
        | ACCIONES
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
            '[SIGEFIV] Click en notificación:',
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
        | OBTENER DATOS
        |--------------------------------------------------------------------------
        */

        const datos =
            event.notification.data || {};


        /*
        |--------------------------------------------------------------------------
        | URL
        |--------------------------------------------------------------------------
        */

        let url =
            datos.url ||
            '/dashboard';


        /*
        |--------------------------------------------------------------------------
        | ASEGURAR URL CORRECTA PARA ASAMBLEAS
        |--------------------------------------------------------------------------
        */

        if (
            datos.tipo === 'asamblea' &&
            datos.asamblea_id
        ) {

            url =
                `/asambleas/${datos.asamblea_id}/citacion`;

        }


        /*
        |--------------------------------------------------------------------------
        | CONVERTIR A URL ABSOLUTA
        |--------------------------------------------------------------------------
        */

        let urlFinal;

        try {

            urlFinal =
                new URL(
                    url,
                    self.location.origin
                ).href;

        } catch (error) {

            console.error(
                '[SIGEFIV] URL inválida:',
                error
            );

            urlFinal =
                new URL(
                    '/dashboard',
                    self.location.origin
                ).href;

        }


        console.log(
            '[SIGEFIV] Abriendo:',
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
        | ABRIR CITACIÓN
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
                    | BUSCAR SIGEFIV ABIERTO
                    |--------------------------------------------------------------------------
                    */

                    for (
                        const client of clients
                    ) {


                        if (
                            client.url.startsWith(
                                self.location.origin
                            )
                        ) {


                            return client
                                .navigate(urlFinal)
                                .then(() => {

                                    return client.focus();

                                })
                                .catch(error => {

                                    console.error(
                                        '[SIGEFIV] Error al navegar:',
                                        error
                                    );


                                    if (
                                        self.clients.openWindow
                                    ) {

                                        return self.clients.openWindow(
                                            urlFinal
                                        );

                                    }

                                });

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SIGEFIV NO ESTÁ ABIERTO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        self.clients.openWindow
                    ) {

                        return self.clients.openWindow(
                            urlFinal
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
*/

self.addEventListener(
    'fetch',
    event => {

        // No cacheamos datos dinámicos.

    }
);