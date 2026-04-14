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
            <img src="{{ $itemDefinition->image_path }}" alt="{{ $itemDefinition->name }}" class="form-preview-img" id="icon-preview">
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

@if($itemDefinition)
<div class="card" style="margin-top:1rem;">
    <h3 style="font-size:0.9375rem; margin-bottom:0.75rem;">Генерация иконки через AI</h3>
    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
        <button type="button" id="btn-generate" class="btn btn-with-icon" style="background:var(--admin-gradient-cta); color:#fff; border:none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Сгенерировать
        </button>
        <span id="gen-status" style="color:var(--admin-muted); font-size:0.8125rem;"></span>
    </div>
    <div id="gen-preview" style="margin-top:0.75rem; display:none;">
        <img id="gen-preview-img" src="" alt="Generated" style="width:128px; height:128px; border-radius:var(--admin-radius-sm); background:repeating-conic-gradient(#1a1d28 0% 25%, #2d3348 0% 50%) 50%/16px 16px;">
    </div>
    <div id="gen-error" style="margin-top:0.5rem; display:none; padding:0.5rem 0.75rem; border-radius:var(--admin-radius-sm); background:var(--admin-error-bg); color:var(--admin-error-text); font-size:0.8125rem;"></div>
</div>

<script>
(function() {
    const btn = document.getElementById('btn-generate');
    const status = document.getElementById('gen-status');
    const preview = document.getElementById('gen-preview');
    const previewImg = document.getElementById('gen-preview-img');
    const errorBox = document.getElementById('gen-error');
    const existingPreview = document.getElementById('icon-preview');
    const url = @json(route('admin.item-definitions.generate-icon', [$theme, $itemDefinition]));
    const csrf = @json(csrf_token());
    let running = false;

    btn.addEventListener('click', async function() {
        if (running) return;
        running = true;
        btn.disabled = true;
        btn.style.opacity = '0.6';
        status.textContent = 'Генерация... (может занять до 30 сек)';
        errorBox.style.display = 'none';
        preview.style.display = 'none';

        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });
            const data = await resp.json();

            if (data.success) {
                const ts = '?t=' + Date.now();
                previewImg.src = data.image_url + ts;
                preview.style.display = 'block';
                status.textContent = 'Готово!';
                status.style.color = 'var(--admin-success-text)';
                if (existingPreview) {
                    existingPreview.src = data.image_url + ts;
                }
            } else {
                errorBox.textContent = data.error || 'Неизвестная ошибка';
                errorBox.style.display = 'block';
                status.textContent = '';
            }
        } catch (e) {
            errorBox.textContent = 'Сетевая ошибка: ' + e.message;
            errorBox.style.display = 'block';
            status.textContent = '';
        } finally {
            running = false;
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    });
})();
</script>
@endif
@endsection
