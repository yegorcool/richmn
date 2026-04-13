@extends('admin.layout')
@section('title', 'User #' . $user->id)
@section('page_heading', trim($user->first_name . ' ' . $user->last_name) . ' (#' . $user->id . ')')
@section('content')
<div class="card">
    <h3 class="mb-sm">Профиль</h3>
    <form method="POST" action="/admin/users/{{ $user->id }}">
        @csrf @method('PATCH')
        <div class="grid-4">
            <div class="form-group">
                <label>Уровень</label>
                <input type="number" name="level" value="{{ $user->level }}" min="1" max="50">
            </div>
            <div class="form-group">
                <label>Энергия</label>
                <input type="number" name="energy" value="{{ $user->energy }}" min="0">
            </div>
            <div class="form-group">
                <label>Монеты</label>
                <input type="number" name="coins" value="{{ $user->coins }}" min="0">
            </div>
            <div class="form-group">
                <label>Опыт</label>
                <input type="number" name="experience" value="{{ $user->experience }}" min="0">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>

<div class="card">
    <h3 class="mb-sm">Информация</h3>
    <table>
        <tr><td>Platform</td><td>{{ $user->source }} ({{ $user->platform_id }})</td></tr>
        <tr><td>Username</td><td>{{ $user->username ?? '-' }}</td></tr>
        <tr><td>Premium</td><td>{{ $user->is_premium ? 'Да' : 'Нет' }}</td></tr>
        <tr><td>Язык</td><td>{{ $user->language_code }}</td></tr>
        <tr><td>Реферальный код</td><td>{{ $user->referral_code }}</td></tr>
        <tr><td>Создан</td><td>{{ $user->created_at }}</td></tr>
        <tr><td>Последняя активность</td><td>{{ $user->last_activity }}</td></tr>
    </table>
</div>

<div class="card user-field-card">
    <style>
        .user-field-card { --uf-cell: 80px; --uf-gap: 6px; }
        .user-field-card .uf-intro { font-size: 0.875rem; color: var(--admin-muted); margin: 0 0 1rem; line-height: 1.45; }
        .user-field-card .uf-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; font-size: 0.8125rem; color: var(--admin-muted); }
        .user-field-card .uf-legend span { display: inline-flex; align-items: center; gap: 0.35rem; }
        .user-field-card .uf-legend i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
        .user-field-card .uf-legend .uf-dot-item { background: linear-gradient(135deg, rgba(64,221,255,0.5), rgba(118,18,250,0.35)); border: 1px solid var(--admin-border); }
        .user-field-card .uf-legend .uf-dot-gen { background: linear-gradient(135deg, rgba(250,18,227,0.35), rgba(118,18,250,0.25)); border: 1px solid var(--admin-border); }
        .user-field-scroll { overflow-x: auto; padding-bottom: 0.25rem; margin-bottom: 1.25rem; }
        .user-field-grid {
            display: grid;
            gap: var(--uf-gap);
            width: max-content;
            margin: 0 auto;
        }
        .user-field-grid .uf-corner { grid-column: 1; grid-row: 1; }
        .user-field-grid .uf-col-head {
            display: flex; align-items: center; justify-content: center;
            font-size: 0.6875rem; font-weight: 700; color: var(--admin-muted);
            letter-spacing: 0.04em; text-transform: uppercase;
            min-width: var(--uf-cell);
            height: 1.5rem;
        }
        .user-field-grid .uf-row-head {
            display: flex; align-items: center; justify-content: center;
            font-size: 0.6875rem; font-weight: 700; color: var(--admin-muted);
            width: 1.75rem;
            min-height: var(--uf-cell);
        }
        .user-field-cell {
            width: var(--uf-cell);
            height: var(--uf-cell);
            min-width: var(--uf-cell);
            min-height: var(--uf-cell);
            box-sizing: border-box;
            border-radius: var(--admin-radius-sm);
            border: 1px solid var(--admin-border);
            background: var(--admin-surface);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            padding: 5px;
            gap: 4px;
            position: relative;
            overflow: hidden;
        }
        .user-field-cell--empty {
            background: var(--admin-bg);
            background-image: radial-gradient(circle at 1px 1px, var(--admin-border) 1px, transparent 0);
            background-size: 10px 10px;
            opacity: 0.85;
        }
        .user-field-cell--clash {
            border-color: var(--admin-warning);
            box-shadow: inset 0 0 0 1px var(--admin-warning-muted);
        }
        .user-field-cell .uf-clash {
            font-size: 0.5625rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; color: var(--admin-warning);
            text-align: center; line-height: 1.2;
            flex-shrink: 0;
        }
        .user-field-cell .uf-stack { flex: 1; display: flex; flex-direction: column; gap: 3px; min-height: 0; justify-content: center; }
        .user-field-entity {
            border-radius: 6px;
            padding: 3px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 2px;
            min-height: 0;
            flex: 1;
        }
        .user-field-entity--item { background: var(--admin-primary-muted); }
        .user-field-entity--gen { background: var(--admin-accent-muted); }
        .user-field-entity .uf-badge {
            font-size: 0.5rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--admin-muted); line-height: 1;
        }
        .user-field-entity .uf-img {
            width: 100%; flex: 1; min-height: 28px; max-height: 48px;
            display: flex; align-items: center; justify-content: center;
        }
        .user-field-entity .uf-img img {
            max-width: 100%; max-height: 100%; width: auto; height: auto;
            object-fit: contain;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
        .user-field-cell--dual .user-field-entity .uf-img { max-height: 30px; min-height: 22px; }
        .user-field-entity .uf-fallback {
            width: 36px; height: 36px; border-radius: 6px;
            background: var(--admin-surface-hover);
            color: var(--admin-muted);
            font-size: 0.875rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .user-field-cell--dual .user-field-entity .uf-fallback { width: 24px; height: 24px; font-size: 0.6875rem; }
        .user-field-entity .uf-meta {
            font-size: 0.5625rem; color: var(--admin-text);
            opacity: 0.9; line-height: 1.15; max-width: 100%;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .user-field-entity .uf-title { font-size: 0.5625rem; color: var(--admin-muted); line-height: 1.15; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .user-field-tables h4 { font-size: 1rem; margin: 0 0 0.5rem; color: var(--admin-text); }
        .user-field-tables .uf-thumb {
            width: 44px; height: 44px; border-radius: var(--admin-radius-sm);
            border: 1px solid var(--admin-border);
            background: var(--admin-surface);
            display: flex; align-items: center; justify-content: center;
            padding: 4px;
        }
        .user-field-tables .uf-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; image-rendering: pixelated; image-rendering: crisp-edges; }
        .user-field-tables .uf-thumb--empty { color: var(--admin-muted); font-size: 0.75rem; }
    </style>
    <h3 class="mb-sm">Игровое поле</h3>
    <p class="uf-intro">Сетка {{ $gridW }}×{{ $gridH }}: X слева направо, Y сверху вниз. Ячейки одного размера; иконки из определений предметов (цепочка).</p>
    @if($fieldItems->isEmpty() && $fieldGenerators->isEmpty())
        <p>На поле нет предметов и генераторов.</p>
    @else
        <div class="uf-legend">
            <span><i class="uf-dot-item" aria-hidden="true"></i> Предмет</span>
            <span><i class="uf-dot-gen" aria-hidden="true"></i> Генератор</span>
        </div>
        <div class="user-field-scroll">
            <div
                class="user-field-grid"
                style="grid-template-columns: 1.75rem repeat({{ $gridW }}, var(--uf-cell)); grid-template-rows: 1.5rem repeat({{ $gridH }}, var(--uf-cell));"
            >
                <div class="uf-corner"></div>
                @for($x = 0; $x < $gridW; $x++)
                    <div class="uf-col-head" style="grid-column: {{ $x + 2 }}; grid-row: 1;">{{ $x }}</div>
                @endfor
                @for($y = 0; $y < $gridH; $y++)
                    <div class="uf-row-head" style="grid-column: 1; grid-row: {{ $y + 2 }};">{{ $y }}</div>
                    @for($x = 0; $x < $gridW; $x++)
                        @php
                            $itemCell = $itemGridCells[$y][$x] ?? null;
                            $genCell = $generatorGridCells[$y][$x] ?? null;
                            $dual = $itemCell && $genCell;
                            $empty = !$itemCell && !$genCell;
                        @endphp
                        <div
                            class="user-field-cell {{ $empty ? 'user-field-cell--empty' : '' }} {{ $dual ? 'user-field-cell--clash user-field-cell--dual' : '' }}"
                            style="grid-column: {{ $x + 2 }}; grid-row: {{ $y + 2 }};"
                        >
                            @if($dual)
                                <div class="uf-clash">Конфликт клетки</div>
                            @endif
                            @if(!$empty)
                                <div class="uf-stack">
                                    @if($itemCell)
                                        <div class="user-field-entity user-field-entity--item" title="{{ $itemCell->definition_name ?? '' }} · {{ $itemCell->theme?->slug ?? '' }} · L{{ $itemCell->item_level }}">
                                            <span class="uf-badge">Предмет</span>
                                            <div class="uf-img">
                                                @if($itemCell->image_href)
                                                    <img src="{{ $itemCell->image_href }}" alt="" loading="lazy" decoding="async">
                                                @else
                                                    <span class="uf-fallback">?</span>
                                                @endif
                                            </div>
                                            <span class="uf-meta">L{{ $itemCell->item_level }}</span>
                                            @if($itemCell->definition_name)
                                                <span class="uf-title">{{ $itemCell->definition_name }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($genCell)
                                        <div class="user-field-entity user-field-entity--gen" title="{{ $genCell->definition_name ?? '' }}">
                                            <span class="uf-badge">Ген.</span>
                                            <div class="uf-img">
                                                @if($genCell->image_href)
                                                    <img src="{{ $genCell->image_href }}" alt="" loading="lazy" decoding="async">
                                                @else
                                                    <span class="uf-fallback">G</span>
                                                @endif
                                            </div>
                                            <span class="uf-meta">
                                                L{{ $genCell->level }} · {{ $genCell->charges_left }}/{{ $genCell->max_charges }}
                                            </span>
                                            @if($genCell->cooldown_until && $genCell->cooldown_until->isFuture())
                                                <span class="uf-title">{{ $genCell->cooldown_until->format('H:i') }}</span>
                                            @elseif($genCell->definition_name)
                                                <span class="uf-title">{{ $genCell->definition_name }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
        </div>
        <div class="user-field-tables">
        @if($fieldItems->isNotEmpty())
        <h4>Предметы по координатам</h4>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>X</th>
                    <th>Y</th>
                    <th>Тема</th>
                    <th>Уровень</th>
                    <th>Название</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fieldItems as $item)
                <tr>
                    <td>
                        <div class="uf-thumb">
                            @if($item->image_href)
                                <img src="{{ $item->image_href }}" alt="" loading="lazy" decoding="async">
                            @else
                                <span class="uf-thumb--empty">—</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $item->grid_x }}</td>
                    <td>{{ $item->grid_y }}</td>
                    <td>{{ $item->theme?->name ?? $item->theme?->slug ?? $item->theme_id }}</td>
                    <td>{{ $item->item_level }}</td>
                    <td>{{ $item->definition_name ?? '—' }}</td>
                    <td>{{ $item->id }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @if($fieldGenerators->isNotEmpty())
        <h4 style="margin-top: 1rem;">Генераторы по координатам</h4>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>X</th>
                    <th>Y</th>
                    <th>Тема</th>
                    <th>Уровень</th>
                    <th>Заряды / кулдаун</th>
                    <th>Название (цепочка)</th>
                    <th>ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fieldGenerators as $gen)
                <tr>
                    <td>
                        <div class="uf-thumb">
                            @if($gen->image_href)
                                <img src="{{ $gen->image_href }}" alt="" loading="lazy" decoding="async">
                            @else
                                <span class="uf-thumb--empty">—</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $gen->grid_x }}</td>
                    <td>{{ $gen->grid_y }}</td>
                    <td>{{ $gen->theme?->name ?? $gen->theme?->slug ?? $gen->theme_id }}</td>
                    <td>{{ $gen->level }}</td>
                    <td>
                        {{ $gen->charges_left }}/{{ $gen->max_charges }}
                        @if($gen->cooldown_until)
                            · {{ $gen->cooldown_until }}
                        @endif
                    </td>
                    <td>{{ $gen->definition_name ?? '—' }}</td>
                    <td>{{ $gen->id }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        </div>
    @endif
</div>

@if($user->characterRelationships->isNotEmpty())
<div class="card">
    <h3 class="mb-sm">Отношения с персонажами</h3>
    <table>
        <thead><tr><th>Персонаж</th><th>Заказов</th><th>Уровень</th></tr></thead>
        <tbody>
            @foreach($user->characterRelationships as $rel)
            <tr>
                <td>{{ $rel->character?->name ?? 'N/A' }}</td>
                <td>{{ $rel->orders_completed }}</td>
                <td>{{ $rel->relationship_level }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <h3 class="mb-sm">Последние заказы</h3>
    <table>
        <thead><tr><th>ID</th><th>Статус</th><th>Создан</th><th>Завершён</th></tr></thead>
        <tbody>
            @foreach($user->orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->status }}</td>
                <td>{{ $order->created_at }}</td>
                <td>{{ $order->completed_at ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
