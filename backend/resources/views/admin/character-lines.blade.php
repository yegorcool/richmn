@extends('admin.layout')
@section('title', 'Реплики: ' . $character->name)
@section('content')
<div class="header">
    <form action="/admin/characters/{{ $character->id }}/lines" method="GET">
        <select name="trigger" onchange="this.form.submit()">
            <option value="">Все триггеры</option>
            @foreach($triggers as $t)
                <option value="{{ $t }}" {{ request('trigger') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <h3 class="mb-sm">Добавить реплику</h3>
    <form method="POST" action="/admin/characters/{{ $character->id }}/lines">
        @csrf
        <div class="grid-1-2">
            <div class="form-group">
                <label>Триггер</label>
                <select name="trigger" required>
                    <option value="order_appear">order_appear</option>
                    <option value="order_complete">order_complete</option>
                    <option value="order_partial">order_partial</option>
                    <option value="order_waiting_long">order_waiting_long</option>
                    <option value="order_waiting_very_long">order_waiting_very_long</option>
                    <option value="merge_nearby">merge_nearby</option>
                    <option value="high_level_item">high_level_item</option>
                    <option value="energy_depleted">energy_depleted</option>
                    <option value="player_return">player_return</option>
                    <option value="event_start">event_start</option>
                    <option value="chain_merge">chain_merge</option>
                    <option value="idle_on_field">idle_on_field</option>
                    <option value="wrong_merge_attempt">wrong_merge_attempt</option>
                </select>
            </div>
            <div class="form-group">
                <label>Текст реплики</label>
                <input type="text" name="text" required class="input-block">
            </div>
            <div class="form-group">
                <label>Приоритет (1-100)</label>
                <input type="number" name="priority" value="50" min="1" max="100">
            </div>
            <div class="form-group">
                <label>Условия (JSON)</label>
                <input type="text" name="conditions" value="{}" class="input-block">
            </div>
            <div class="form-group">
                <label>Макс. показов</label>
                <input type="number" name="max_shows" value="10" min="1">
            </div>
            <div class="form-group">
                <label>Кулдаун (часы)</label>
                <input type="number" name="cooldown_hours" value="24" min="0">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Добавить</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th></th><th>ID</th><th>Триггер</th><th>Текст</th><th>Приоритет</th><th>Условия</th><th>Макс</th></tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td>
                    <form method="POST" action="/admin/lines/{{ $line->id }}" class="inline-form" onsubmit="return confirm('Удалить?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon--danger" title="Удалить" aria-label="Удалить">@include('admin.icons.trash')</button>
                    </form>
                </td>
                <td>{{ $line->id }}</td>
                <td><code>{{ $line->trigger }}</code></td>
                <td style="max-width: 300px;">{{ $line->text }}</td>
                <td>{{ $line->priority }}</td>
                <td><code style="font-size:10px;">{{ json_encode($line->conditions) }}</code></td>
                <td>{{ $line->max_shows }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div>{{ $lines->links('vendor.pagination.admin') }}</div>
</div>
@endsection
