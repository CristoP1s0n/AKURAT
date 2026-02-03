<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Login - AKURAT</title>
        <!-- Masukkan Tailwind & Font Awesome di sini jika belum ada -->
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body>
        <div class="font-sans antialiased">
            {{ $slot }}
        </div>
    </body>
</html>