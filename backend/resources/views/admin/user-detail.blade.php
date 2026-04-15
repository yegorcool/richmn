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
        /**
         * Mirrors frontend GamePage background + GameField Pixi checkerboard (CELL_SIZE 56, GAP 4, colors from GameField.tsx).
         */
        .user-field-card .uf-game-wrap {
            background: linear-gradient(180deg, #fff5e6 0%, #f0e6d6 100%);
            margin: -0.5rem -1rem 0;
            padding: 1rem 1rem 1.25rem;
            border-radius: var(--admin-radius, 8px);
        }
        .user-field-card .uf-game-board {
            --uf-cell: 56px;
            --uf-gap: 4px;
            --uf-light: #d0bb99;
            --uf-dark: #cbad87;
            width: calc({{ $gridW }} * (var(--uf-cell) + var(--uf-gap)) + var(--uf-gap));
            height: calc({{ $gridH }} * (var(--uf-cell) + var(--uf-gap)) + var(--uf-gap));
            margin: 0 auto;
            padding: var(--uf-gap);
            box-sizing: border-box;
            background: var(--uf-light);
            border-radius: 16px;
            overflow: hidden;
            display: grid;
            grid-template-columns: repeat({{ $gridW }}, var(--uf-cell));
            grid-template-rows: repeat({{ $gridH }}, var(--uf-cell));
            gap: var(--uf-gap);
        }
        .user-field-card .uf-cell {
            width: var(--uf-cell);
            height: var(--uf-cell);
            min-width: 0;
            min-height: 0;
            box-sizing: border-box;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-field-card .uf-cell--dark {
            background: var(--uf-dark);
            border-radius: 4px;
        }
        .user-field-card .uf-cell--light {
            background: var(--uf-light);
        }
        .user-field-card .uf-cell--clash {
            box-shadow: inset 0 0 0 2px rgba(230, 160, 50, 0.85);
            border-radius: 4px;
        }
        .user-field-card .uf-stack {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            pointer-events: none;
        }
        .user-field-card .uf-stack--dual {
            gap: 0;
            justify-content: space-evenly;
        }
        .user-field-card .uf-icon {
            width: var(--uf-cell);
            height: var(--uf-cell);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-field-card .uf-stack--dual .uf-icon {
            width: 100%;
            height: calc((var(--uf-cell) - 2px) / 2);
        }
        .user-field-card .uf-icon img {
            width: var(--uf-cell);
            height: var(--uf-cell);
            object-fit: contain;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
            display: block;
        }
        .user-field-card .uf-stack--dual .uf-icon img {
            width: 100%;
            height: 100%;
        }
        .user-field-card .uf-ph {
            width: calc(var(--uf-cell) - 6px);
            height: calc(var(--uf-cell) - 6px);
            border-radius: 10px;
            background: rgba(216, 200, 174, 0.85);
        }
        .user-field-card .uf-item-frame {
            border-radius: 10px;
            outline: 2px solid #ffd700;
            outline-offset: -2px;
        }
        .user-field-card .uf-gen {
            width: calc(var(--uf-cell) - 10px);
            height: calc(var(--uf-cell) - 10px);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .user-field-card .uf-gen--active {
            border: 2px solid rgba(248, 246, 242, 0.95);
        }
        .user-field-card .uf-gen--inactive {
            border: 2px solid rgba(58, 52, 44, 0.9);
        }
        .user-field-card .uf-gen img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
        .user-field-card .uf-gen--inactive img {
            filter: saturate(0.65) brightness(0.92);
        }
        .user-field-card .uf-stack--dual .uf-gen {
            width: calc(100% - 6px);
            height: calc((var(--uf-cell) - 8px) / 2);
        }
    </style>
    <h3 class="mb-sm">Игровое поле</h3>
    <div class="uf-game-wrap">
        <div class="uf-game-board">
            @for($y = 0; $y < $gridH; $y++)
                @for($x = 0; $x < $gridW; $x++)
                    @php
                        $itemCell = $itemGridCells[$y][$x] ?? null;
                        $genCell = $generatorGridCells[$y][$x] ?? null;
                        $dual = $itemCell && $genCell;
                        $checkerDark = (($x + $y) % 2) === 1;
                        $genInactive = $genCell && $genCell->charges_left <= 0 && $genCell->cooldown_until && $genCell->cooldown_until->isFuture();
                    @endphp
                    <div
                        class="uf-cell {{ $checkerDark ? 'uf-cell--dark' : 'uf-cell--light' }} {{ $dual ? 'uf-cell--clash' : '' }}"
                    >
                        @if($itemCell || $genCell)
                            <div class="uf-stack {{ $dual ? 'uf-stack--dual' : '' }}">
                                @if($itemCell)
                                    <div class="uf-icon {{ $itemCell->item_level >= 8 ? 'uf-item-frame' : '' }}">
                                        @if($itemCell->image_href)
                                            <img src="{{ $itemCell->image_href }}" alt="" loading="lazy" decoding="async">
                                        @else
                                            <span class="uf-ph"></span>
                                        @endif
                                    </div>
                                @endif
                                @if($genCell)
                                    <div class="uf-icon uf-gen {{ $genInactive ? 'uf-gen--inactive' : 'uf-gen--active' }}">
                                        @if($genCell->image_href)
                                            <img src="{{ $genCell->image_href }}" alt="" loading="lazy" decoding="async">
                                        @else
                                            <span class="uf-ph"></span>
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
