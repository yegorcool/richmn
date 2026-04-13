@extends('admin.layout')
@section('title', 'Статистика')
@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="value">{{ number_format($stats['total_users']) }}</div>
        <div class="label">Всего пользователей</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['dau']) }}</div>
        <div class="label">DAU (сегодня)</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['wau']) }}</div>
        <div class="label">WAU (7 дней)</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['new_users_today']) }}</div>
        <div class="label">Новых сегодня</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['orders_today']) }}</div>
        <div class="label">Заказов сегодня</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['ads_today']) }}</div>
        <div class="label">Просмотров рекламы</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['rewarded_today']) }}</div>
        <div class="label">Rewarded Ads</div>
    </div>
    <div class="stat-card">
        <div class="value">{{ number_format($stats['new_users_week']) }}</div>
        <div class="label">Новых за неделю</div>
    </div>
</div>

<div class="card mt-lg">
    <h3 class="mb-sm">Retention</h3>
    <table>
        <tr><th>Метрика</th><th>Значение</th><th>Цель</th></tr>
        <tr><td>D1 Retention</td><td>{{ $retention['d1'] }}%</td><td>≥ 40%</td></tr>
        <tr><td>D7 Retention</td><td>{{ $retention['d7'] }}%</td><td>≥ 20%</td></tr>
        <tr><td>D30 Retention</td><td>{{ $retention['d30'] }}%</td><td>≥ 10%</td></tr>
    </table>
</div>
@endsection
