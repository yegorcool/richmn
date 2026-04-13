@extends('admin.layout')
@section('title', 'Пользователи')
@section('content')
<div class="header">
    <form action="/admin/users" method="GET">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по имени / ID">
        <button type="submit" class="btn btn-primary">Найти</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th></th>
                <th>ID</th>
                <th>Платформа</th>
                <th>Имя</th>
                <th>Username</th>
                <th>Уровень</th>
                <th>Энергия</th>
                <th>Монеты</th>
                <th>Последняя активность</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>
                    <a href="/admin/users/{{ $user->id }}" class="btn-icon btn-icon--primary" title="Открыть" aria-label="Открыть">@include('admin.icons.eye')</a>
                </td>
                <td>{{ $user->id }}</td>
                <td>{{ $user->source }}</td>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                <td>{{ $user->username ?? '-' }}</td>
                <td>{{ $user->level }}</td>
                <td>{{ $user->energy }}/{{ config('game.energy.max') }}</td>
                <td>{{ number_format($user->coins) }}</td>
                <td>{{ $user->last_activity?->diffForHumans() ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div>{{ $users->links('vendor.pagination.admin') }}</div>
</div>
@endsection
