@extends('admin.layout')
@section('title', 'Предметы: ' . $theme->name)
@section('content')
<div class="header">
    <div>
        <a href="{{ route('admin.themes') }}" class="btn btn-secondary btn-with-icon">@include('admin.icons.arrow-left') К наборам</a>
        <a href="{{ route('admin.item-definitions.create', $theme) }}" class="btn btn-primary btn-with-icon">@include('admin.icons.plus') Добавить предмет</a>
    </div>
</div>

<div class="card card--muted">
    <div class="grid-4" style="font-size:0.8125rem;">
        <div><strong>Генератор:</strong> {{ $theme->generator_name }} ({{ $theme->generator_type }})</div>
        <div><strong>Энергия:</strong> {{ $theme->generator_energy_cost }} за генерацию</div>
        <div><strong>Лимит:</strong> {{ $theme->generator_generation_limit }} генераций</div>
        <div><strong>Таймаут:</strong> {{ $theme->generator_generation_timeout }}с</div>
    </div>
</div>

<div class="card">
    @if($items->isEmpty())
        <p class="text-muted text-center" style="padding:1.25rem;">Нет предметов. Добавьте первый предмет в цепочку.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Действия</th>
                <th style="width:50px;">Ур.</th>
                <th style="width:80px;">Иконка</th>
                <th>Название</th>
                <th>Slug</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <span class="actions-row">
                        <a href="{{ route('admin.item-definitions.edit', [$theme, $item]) }}" class="btn-icon btn-icon--warning" title="Редактировать" aria-label="Редактировать">@include('admin.icons.pencil')</a>
                        <form action="{{ route('admin.item-definitions.delete', [$theme, $item]) }}" method="POST" class="inline-form" onsubmit="return confirm('Удалить предмет?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon--danger" title="Удалить" aria-label="Удалить">@include('admin.icons.trash')</button>
                        </form>
                    </span>
                </td>
                <td>
                    <span class="level-badge">{{ $item->level }}</span>
                </td>
                <td>
                    @if($item->image_path)
                        <img src="{{ $item->image_path }}" alt="{{ $item->name }}" class="thumb-48">
                    @else
                        <span class="thumb-placeholder">—</span>
                    @endif
                </td>
                <td>{{ $item->name }}</td>
                <td><code>{{ $item->slug }}</code></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if($items->count() >= 2)
<div class="card">
    <h3 class="mb-sm" style="font-size:0.9375rem;">Цепочка merge</h3>
    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
        @foreach($items as $idx => $item)
            <div class="text-center">
                @if($item->image_path)
                    <img src="{{ $item->image_path }}" alt="" class="merge-thumb">
                @else
                    <div class="merge-placeholder">Lv{{ $item->level }}</div>
                @endif
                <div class="merge-caption">{{ $item->name }}</div>
            </div>
            @if(!$loop->last)
                <span class="merge-arrow">→</span>
            @endif
        @endforeach
    </div>
</div>
@endif
@endsection
