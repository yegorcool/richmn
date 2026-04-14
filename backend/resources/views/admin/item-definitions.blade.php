@extends('admin.layout')
@section('title', 'Предметы: ' . $theme->name)
@section('content')
<div class="header">
    <div>
        <a href="{{ route('admin.themes') }}" class="btn btn-secondary btn-with-icon">@include('admin.icons.arrow-left') К наборам</a>
        <a href="{{ route('admin.item-definitions.create', $theme) }}" class="btn btn-primary btn-with-icon">@include('admin.icons.plus') Добавить предмет</a>
        @if($items->isNotEmpty())
        <button type="button" id="btn-generate-all" class="btn btn-with-icon" style="background:var(--admin-gradient-cta); color:#fff; border:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Сгенерировать все без иконок
        </button>
        <span id="gen-all-status" style="color:var(--admin-muted); font-size:0.8125rem; margin-left:0.5rem;"></span>
        @endif
    </div>
</div>

<div class="card card--muted">
    <div class="grid-4" style="font-size:0.8125rem;">
        <div><strong>Генератор:</strong> {{ $theme->generator_name }}</div>
        <div><strong>Энергия:</strong> {{ $theme->generator_energy_cost }} за генерацию</div>
        <div><strong>Лимит:</strong> {{ $theme->generator_generation_limit }} генераций</div>
        <div><strong>Таймаут:</strong> {{ $theme->generator_generation_timeout }}с</div>
    </div>
</div>

<div id="gen-all-error" style="display:none; margin-bottom:0.75rem; padding:0.5rem 0.75rem; border-radius:var(--admin-radius-sm); background:var(--admin-error-bg); color:var(--admin-error-text); font-size:0.8125rem;"></div>

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
            <tr data-item-id="{{ $item->id }}" data-has-image="{{ $item->image_url ? '1' : '0' }}">
                <td>
                    <span class="actions-row">
                        <a href="{{ route('admin.item-definitions.edit', [$theme, $item]) }}" class="btn-icon btn-icon--warning" title="Редактировать" aria-label="Редактировать">@include('admin.icons.pencil')</a>
                        <button type="button"
                                class="btn-icon btn-gen-single"
                                title="Сгенерировать иконку"
                                aria-label="Сгенерировать иконку"
                                data-url="{{ route('admin.item-definitions.generate-icon', [$theme, $item]) }}"
                                style="background:var(--admin-secondary-muted); color:var(--admin-secondary);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </button>
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
                        <img src="{{ $item->image_path }}" alt="{{ $item->name }}" class="thumb-48" id="thumb-{{ $item->id }}">
                    @else
                        <span class="thumb-placeholder" id="thumb-{{ $item->id }}">—</span>
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
                    <img src="{{ $item->image_path }}" alt="" class="merge-thumb" id="merge-thumb-{{ $item->id }}">
                @else
                    <div class="merge-placeholder" id="merge-thumb-{{ $item->id }}">Lv{{ $item->level }}</div>
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

@if($items->isNotEmpty())
<script>
(function() {
    const csrf = @json(csrf_token());

    async function generateIcon(url, itemId) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const data = await resp.json();
        if (!data.success) throw new Error(data.error || 'Generation failed');

        const ts = '?t=' + Date.now();
        const thumb = document.getElementById('thumb-' + itemId);
        if (thumb) {
            if (thumb.tagName === 'IMG') {
                thumb.src = data.image_url + ts;
            } else {
                const img = document.createElement('img');
                img.src = data.image_url + ts;
                img.alt = '';
                img.className = 'thumb-48';
                img.id = 'thumb-' + itemId;
                thumb.replaceWith(img);
            }
        }
        const mergeThumb = document.getElementById('merge-thumb-' + itemId);
        if (mergeThumb) {
            if (mergeThumb.tagName === 'IMG') {
                mergeThumb.src = data.image_url + ts;
            } else {
                const img = document.createElement('img');
                img.src = data.image_url + ts;
                img.alt = '';
                img.className = 'merge-thumb';
                img.id = 'merge-thumb-' + itemId;
                mergeThumb.replaceWith(img);
            }
        }
        const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
        if (row) row.dataset.hasImage = '1';

        return data;
    }

    document.querySelectorAll('.btn-gen-single').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            if (btn.disabled) return;
            const url = btn.dataset.url;
            const row = btn.closest('tr');
            const itemId = row?.dataset.itemId;
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.title = 'Генерация...';
            try {
                await generateIcon(url, itemId);
                btn.style.color = 'var(--admin-success-text)';
                btn.title = 'Готово!';
            } catch (e) {
                btn.style.color = 'var(--admin-danger)';
                btn.title = 'Ошибка: ' + e.message;
            } finally {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    });

    const btnAll = document.getElementById('btn-generate-all');
    const statusAll = document.getElementById('gen-all-status');
    const errorAll = document.getElementById('gen-all-error');

    if (btnAll) {
        btnAll.addEventListener('click', async function() {
            if (btnAll.disabled) return;

            const rows = Array.from(document.querySelectorAll('tr[data-has-image="0"]'));
            if (rows.length === 0) {
                statusAll.textContent = 'Все предметы уже имеют иконки';
                statusAll.style.color = 'var(--admin-success-text)';
                return;
            }

            btnAll.disabled = true;
            btnAll.style.opacity = '0.6';
            errorAll.style.display = 'none';
            let done = 0;

            for (const row of rows) {
                const itemId = row.dataset.itemId;
                const btn = row.querySelector('.btn-gen-single');
                const url = btn?.dataset.url;
                if (!url) continue;

                statusAll.textContent = (done + 1) + ' / ' + rows.length + '...';
                try {
                    await generateIcon(url, itemId);
                    done++;
                } catch (e) {
                    errorAll.textContent = 'Ошибка на предмете #' + itemId + ': ' + e.message;
                    errorAll.style.display = 'block';
                    break;
                }
            }

            statusAll.textContent = done + ' / ' + rows.length + ' готово';
            statusAll.style.color = 'var(--admin-success-text)';
            btnAll.disabled = false;
            btnAll.style.opacity = '1';
        });
    }
})();
</script>
@endif
@endsection
