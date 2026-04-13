@extends('admin.layout')
@section('title', $theme ? 'Редактировать набор' : 'Создать набор')
@section('page_heading', $theme ? 'Редактировать набор: ' . $theme->name : 'Создать набор')
@section('content')
<div class="header">
    <a href="{{ route('admin.themes') }}" class="btn btn-secondary btn-with-icon">@include('admin.icons.arrow-left') Назад</a>
</div>

<div class="card">
    <form method="POST" action="{{ $theme ? route('admin.themes.update', $theme) : route('admin.themes.store') }}">
        @csrf
        @if($theme) @method('PUT') @endif

        <div class="form-group">
            <label for="name">Название</label>
            <input type="text" name="name" id="name" value="{{ old('name', $theme?->name) }}" required class="input-block">
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $theme?->slug) }}" required class="input-block">
        </div>

        <div class="form-group">
            <label for="generator_name">Название генератора</label>
            <input type="text" name="generator_name" id="generator_name" value="{{ old('generator_name', $theme?->generator_name) }}" required class="input-block">
        </div>

        <div class="grid-4">
            <div class="form-group">
                <label for="unlock_level">Уровень разблокировки</label>
                <input type="number" name="unlock_level" id="unlock_level" value="{{ old('unlock_level', $theme?->unlock_level ?? 1) }}" min="1" required class="input-block">
            </div>

            <div class="form-group">
                <label for="generator_energy_cost">Энергия за генерацию</label>
                <input type="number" name="generator_energy_cost" id="generator_energy_cost" value="{{ old('generator_energy_cost', $theme?->generator_energy_cost ?? 1) }}" min="0" required class="input-block">
            </div>

            <div class="form-group">
                <label for="generator_generation_limit">Лимит генераций</label>
                <input type="number" name="generator_generation_limit" id="generator_generation_limit" value="{{ old('generator_generation_limit', $theme?->generator_generation_limit ?? 5) }}" min="1" required class="input-block">
            </div>

            <div class="form-group">
                <label for="generator_generation_timeout">Таймаут (сек)</label>
                <input type="number" name="generator_generation_timeout" id="generator_generation_timeout" value="{{ old('generator_generation_timeout', $theme?->generator_generation_timeout ?? 1800) }}" min="0" required class="input-block">
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $theme?->is_active ?? true) ? 'checked' : '' }}>
                Активна
            </label>
        </div>

        <button type="submit" class="btn btn-primary">{{ $theme ? 'Сохранить' : 'Создать' }}</button>
    </form>
</div>
@endsection
