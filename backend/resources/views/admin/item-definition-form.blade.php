@extends('admin.layout')
@section('title', $itemDefinition ? 'Редактировать предмет' : 'Добавить предмет')
@section('page_heading', $itemDefinition ? 'Редактировать: ' . $itemDefinition->name : 'Добавить предмет в ' . $theme->name)
@section('content')
<div class="header">
    <a href="{{ route('admin.item-definitions', $theme) }}" class="btn btn-secondary btn-with-icon">@include('admin.icons.arrow-left') Назад</a>
</div>

<div class="card">
    <form method="POST"
          action="{{ $itemDefinition ? route('admin.item-definitions.update', [$theme, $itemDefinition]) : route('admin.item-definitions.store', $theme) }}"
          enctype="multipart/form-data">
        @csrf
        @if($itemDefinition) @method('PUT') @endif

        <div class="grid-2">
            <div class="form-group">
                <label for="level">Уровень</label>
                <input type="number" name="level" id="level" value="{{ old('level', $itemDefinition?->level ?? $nextLevel) }}" min="1" required class="input-block">
            </div>

            <div class="form-group">
                <label for="name">Название</label>
                <input type="text" name="name" id="name" value="{{ old('name', $itemDefinition?->name) }}" required class="input-block">
            </div>
        </div>

        <div class="form-group">
            <label for="slug">Slug (необязательно, генерируется из названия)</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $itemDefinition?->slug) }}" class="input-block">
        </div>

        <div class="form-group">
            <label for="image">Загрузить картинку (PNG, JPG, SVG, WebP, до 2 МБ)</label>
            <input type="file" name="image" id="image" accept="image/*" class="input-block">
        </div>

        @if($itemDefinition?->image_path)
        <div class="form-group form-row-preview">
            <img src="{{ $itemDefinition->image_path }}" alt="{{ $itemDefinition->name }}" class="form-preview-img">
            <label>
                <input type="hidden" name="remove_image" value="0">
                <input type="checkbox" name="remove_image" value="1">
                Удалить картинку
            </label>
        </div>
        @endif

        <div class="form-group">
            <label for="image_url_external">Или URL внешней картинки</label>
            <input type="url" name="image_url_external" id="image_url_external" value="{{ old('image_url_external') }}" placeholder="https://..." class="input-block">
        </div>

        <button type="submit" class="btn btn-primary">{{ $itemDefinition ? 'Сохранить' : 'Добавить' }}</button>
    </form>
</div>
@endsection
