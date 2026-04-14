@extends('admin.layout')
@section('title', $theme ? 'Редактировать набор' : 'Создать набор')
@section('page_heading', $theme ? 'Редактировать набор: ' . $theme->name : 'Создать набор')
@section('content')
<div class="header">
    <a href="{{ route('admin.themes') }}" class="btn btn-secondary btn-with-icon">@include('admin.icons.arrow-left') Назад</a>
</div>

<div class="card">
    <form method="POST" action="{{ $theme ? route('admin.themes.update', $theme) : route('admin.themes.store') }}" enctype="multipart/form-data">
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

        <div class="form-group">
            <label for="generator_image">Иконка генератора (PNG, JPG, WebP, SVG, до 2 МБ)</label>
            <input type="file" name="generator_image" id="generator_image" accept="image/*" class="input-block">
        </div>

        @if($theme?->generator_image_path)
        <div class="form-group form-row-preview">
            <img src="{{ $theme->generator_image_path }}" alt="Генератор" class="form-preview-img" id="generator-icon-preview">
            <label>
                <input type="hidden" name="remove_generator_image" value="0">
                <input type="checkbox" name="remove_generator_image" value="1">
                Удалить иконку генератора
            </label>
        </div>
        @endif

        <div class="form-group">
            <label for="generator_image_url_external">Или URL внешней иконки генератора</label>
            <input type="url" name="generator_image_url_external" id="generator_image_url_external" value="{{ old('generator_image_url_external') }}" placeholder="https://..." class="input-block">
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

@if($theme)
<div class="card" style="margin-top:1rem;">
    <h3 style="font-size:0.9375rem; margin-bottom:0.75rem;">Иконка генератора через AI</h3>
    <p style="color:var(--admin-muted); font-size:0.8125rem; margin-bottom:0.75rem;">
        Генерация по полю «Название генератора» и стилю референсов (как у предметов). Сохраняется в набор без отправки формы выше.
    </p>
    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
        <button type="button" id="btn-gen-generator" class="btn btn-with-icon" style="background:var(--admin-gradient-cta); color:#fff; border:none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Сгенерировать иконку генератора
        </button>
        <span id="gen-gen-status" style="color:var(--admin-muted); font-size:0.8125rem;"></span>
    </div>
    <div id="gen-gen-error" style="margin-top:0.5rem; display:none; padding:0.5rem 0.75rem; border-radius:var(--admin-radius-sm); background:var(--admin-error-bg); color:var(--admin-error-text); font-size:0.8125rem;"></div>
    <div style="margin-top:0.75rem;">
        <img id="generator-ai-preview" src="{{ $theme->generator_image_path ?? '' }}" alt=""
             style="width:128px; height:128px; border-radius:var(--admin-radius-sm); background:repeating-conic-gradient(#1a1d28 0% 25%, #2d3348 0% 50%) 50%/16px 16px; {{ $theme->generator_image_path ? '' : 'display:none;' }}">
    </div>
</div>

<script>
(function() {
    const btn = document.getElementById('btn-gen-generator');
    const status = document.getElementById('gen-gen-status');
    const errorBox = document.getElementById('gen-gen-error');
    const preview = document.getElementById('generator-icon-preview');
    const aiPreview = document.getElementById('generator-ai-preview');
    const url = @json(route('admin.themes.generate-generator-icon', $theme));
    const csrf = @json(csrf_token());
    let running = false;

    btn.addEventListener('click', async function() {
        if (running) return;
        running = true;
        btn.disabled = true;
        btn.style.opacity = '0.6';
        status.textContent = 'Генерация...';
        errorBox.style.display = 'none';

        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await resp.json();
            if (data.success) {
                const ts = '?t=' + Date.now();
                status.textContent = 'Готово!';
                status.style.color = 'var(--admin-success-text)';
                if (preview) preview.src = data.image_url + ts;
                if (aiPreview) {
                    aiPreview.src = data.image_url + ts;
                    aiPreview.style.display = 'block';
                }
            } else {
                errorBox.textContent = data.error || 'Ошибка';
                errorBox.style.display = 'block';
                status.textContent = '';
            }
        } catch (e) {
            errorBox.textContent = 'Сеть: ' + e.message;
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
