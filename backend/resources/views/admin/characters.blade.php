@extends('admin.layout')
@section('title', 'Персонажи')
@section('content')
<div class="card">
    <table>
        <thead>
            <tr><th></th><th>ID</th><th>Имя</th><th>Тематика</th><th>Уровень</th><th>Реплик</th></tr>
        </thead>
        <tbody>
            @foreach($characters as $char)
            <tr>
                <td>
                    <a href="/admin/characters/{{ $char->id }}/lines" class="btn-icon btn-icon--secondary" title="Реплики" aria-label="Реплики">@include('admin.icons.message')</a>
                </td>
                <td>{{ $char->id }}</td>
                <td>{{ $char->name }}</td>
                <td>{{ $char->theme?->name ?? 'Все' }}</td>
                <td>{{ $char->unlock_level }}</td>
                <td>{{ $char->lines_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
