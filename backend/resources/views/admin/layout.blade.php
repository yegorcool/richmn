<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richmn Admin - @yield('title')</title>
    @include('admin.partials.favicon')
    @include('admin.partials.theme')
</head>
<body class="admin-app">
    <aside class="sidebar">
        <nav>
            <a href="/admin" class="{{ request()->is('admin') && !request()->is('admin/*') ? 'active' : '' }}">Статистика</a>
            <a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">Пользователи</a>
            <a href="/admin/themes" class="{{ request()->is('admin/themes*') ? 'active' : '' }}">Тематики</a>
            <a href="/admin/characters" class="{{ request()->is('admin/characters*') ? 'active' : '' }}">Персонажи</a>
        </nav>
    </aside>
    <div class="main">
        <header class="admin-topbar">
            <h1 class="admin-topbar__title">
                @hasSection('page_heading')
                    @yield('page_heading')
                @else
                    @yield('title')
                @endif
            </h1>
            <form class="admin-topbar__logout" action="/admin/logout" method="POST">
                @csrf
                <button type="submit" class="btn btn-topbar-logout">Выход</button>
            </form>
        </header>
        <div class="admin-main-inner">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
