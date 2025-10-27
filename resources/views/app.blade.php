<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificador UNIFRANZ</title>
    {{-- Token CSRF para peticiones Axios/fetch --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <router-view></router-view>
    </div>
</body>
</html>