<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login - {{ config('app.name', 'AKURAT') }}</title>

        <!-- Tailwind CSS & Fonts -->
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <style>
            body {
                font-family: 'Inter', sans-serif;
                /* Gradien Biru sesuai tema AKURAT */
                background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%);
                background-attachment: fixed;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .glass-strong {
                background: rgba(255, 255, 255, 0.12);
                backdrop-filter: blur(25px);
                -webkit-backdrop-filter: blur(25px);
                border: 1.5px solid rgba(255, 255, 255, 0.3);
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="w-full sm:max-w-md px-6">
            {{ $slot }}
        </div>
    </body>
</html>