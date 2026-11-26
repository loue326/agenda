<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Agenda')</title>
    <link rel="stylesheet" href="{{ asset('css/events.css') }}?v=2">
</head>
<body>
<header class="site-header">
    <a class="brand" href="{{ route('dashboard') }}">Agenda</a>
    <nav>
        <a href="{{ route('dashboard') }}">Tableau de bord</a>
        <a href="{{ route('events.index') }}">Événements</a>
        <a href="{{ route('categories.index') }}">Catégories</a>
        <form class="inline-form" action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="logout-button" type="submit">Déconnexion</button>
        </form>
    </nav>
</header>
<main class="app-content">
    @if(session('success'))<p class="success-message">{{ session('success') }}</p>@endif
    @yield('content')
</main>
<script src="{{ asset('js/api.js') }}"></script>
@stack('scripts')
</body>
</html>
