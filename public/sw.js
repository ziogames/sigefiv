const CACHE_NAME = 'sigefiv-v1';


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


    let datos = {
        title: 'SIGEFIV',
        body: 'Tienes una nueva notificación.',
        url: '/dashboard',
        icon: '/assets/pwa/icon-192.png',
        badge: '/assets/pwa/icon-192.png'
    };


    /*
    | Intentamos leer el payload enviado por Laravel.
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
    | Mostramos la notificación.
    */

    event.waitUntil(

        self.registration.showNotification(
            datos.title,
            {
                body: datos.body,

                icon: datos.icon,

                badge: datos.badge,

                data: {
                    url: datos.url
                },

                vibrate: [
                    200,
                    100,
                    200
                ],

                requireInteraction: false
            }
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
            '[SIGEFIV] Notificación seleccionada'
        );


        event.notification.close();


        const url =
            event.notification.data?.url ||
            '/dashboard';


        event.waitUntil(

            self.clients
                .matchAll({
                    type: 'window',
                    includeUncontrolled: true
                })
                .then(clients => {

                    /*
                    | Si SIGEFIV ya está abierto,
                    | llevamos al usuario a esa ventana.
                    */

                    for (const client of clients) {

                        if (
                            'focus' in client
                        ) {

                            client.navigate(url);

                            return client.focus();

                        }

                    }


                    /*
                    | Si no está abierto,
                    | abrimos SIGEFIV.
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
| Por ahora dejamos las peticiones pasar directamente a Laravel.
| No cacheamos datos financieros ni páginas dinámicas.
|
*/

self.addEventListener('fetch', event => {

    // Las peticiones pasan directamente a Laravel.

});