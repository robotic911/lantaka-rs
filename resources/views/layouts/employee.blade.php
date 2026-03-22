<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Employee - Layout</title>
    
    <link rel="stylesheet" href="{{ asset('css/employee.css') }}">
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    @stack('styles')
</head>

<body> 
    <aside class="sidebar">  
        @include('partials.employee.side_nav')
    </aside>

    <main class="main-container">
        <header class="header">
            @include('partials.employee.top_nav')
        </header>
      
        @yield('content')
    </main>
    <x-toaster />
</body>
</html>