<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lantaka Room and Venue Reservation Portal</title>
    <link rel="stylesheet" href="{{ asset('css/client.css') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <!-- Header -->
    <header class="header">
      @include('partials.client.header')
    </header>

    <!-- Hero Section -->
    <main class="main-container">
        @yield('content')
    </main>
    <x-toaster />
</body>
</html>
