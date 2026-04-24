<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificador UNIFRANZ</title>
    {{-- Token CSRF para peticiones Axios/fetch --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- PWA Manifest --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4F46E5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Calificador">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/js/app.js'])
    {{-- Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('✅ Service Worker registrado:', registration.scope);
                        
                        // Verificar actualizaciones cada hora
                        setInterval(() => {
                            registration.update();
                        }, 3600000);
                    })
                    .catch((error) => {
                        console.error('❌ Error registrando Service Worker:', error);
                    });
                
                // Escuchar mensajes del Service Worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    console.log('Mensaje del SW:', event.data);
                });
            });
        }
    </script>
</head>
<body>
    <div id="app">
        <router-view></router-view>
    </div>
</body>
</html>