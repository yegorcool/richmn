@extends('admin.layout')
@section('title', 'Наборы предметов (тематики)')
@section('content')
<div class="header">
    <a href="{{ route('admin.themes.create') }}" class="btn btn-primary btn-with-icon">@include('admin.icons.plus') Создать набор</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Действия</th>
                <th>ID</th>
                <th>Название</th>
                <th>Slug</th>
                <th>Генератор</th>
                <th>Разблокировка</th>
                <th>Предметов</th>
                <th>Энергия</th>
                <th>Лимит</th>
                <th>Таймаут</th>
                <th>Активна</th>
            </tr>
        </thead>
        <tbody>
            @foreach($themes as $theme)
            <tr>
                <td>
                    <span class="actions-row">
                        <a href="{{ route('admin.item-definitions', $theme) }}" class="btn-icon btn-icon--primary" title="Просмотр предметов" aria-label="Просмотр предметов">@include('admin.icons.eye')</a>
                        <a href="{{ route('admin.themes.edit', $theme) }}" class="btn-icon btn-icon--warning" title="Редактировать" aria-label="Редактировать">@include('admin.icons.pencil')</a>
                        <form action="{{ route('admin.themes.delete', $theme) }}" method="POST" class="inline-form" onsubmit="return confirm('Удалить набор и все предметы?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon--danger" title="Удалить" aria-label="Удалить">@include('admin.icons.trash')</button>
                        </form>
                    </span>
                </td>
                <td>{{ $theme->id }}</td>
                <td>{{ $theme->name }}</td>
                <td><code>{{ $theme->slug }}</code></td>
                <td>{{ $theme->generator_name }}</td>
                <td>Lv.{{ $theme->unlock_level }}</td>
                <td>
                    <a href="{{ route('admin.item-definitions', $theme) }}" class="link-accent">
                        {{ $theme->item_definitions_count }} шт.
                    </a>
                </td>
                <td>{{ $theme->generator_energy_cost }}</td>
                <td>{{ $theme->generator_generation_limit }}</td>
                <td>{{ $theme->generator_generation_timeout }}с</td>
                <td>{{ $theme->is_active ? '✅' : '❌' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
