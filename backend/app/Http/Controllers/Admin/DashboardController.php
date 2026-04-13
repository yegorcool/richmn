<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdView;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $weekAgo = Carbon::today()->subDays(7);

        $stats = [
            'total_users' => User::count(),
            'dau' => User::where('last_activity', '>=', $today)->count(),
            'wau' => User::where('last_activity', '>=', $weekAgo)->count(),
            'orders_today' => Order::where('status', 'completed')->where('completed_at', '>=', $today)->count(),
            'ads_today' => AdView::where('viewed_at', '>=', $today)->count(),
            'rewarded_today' => AdView::where('format', 'rewarded')->where('viewed_at', '>=', $today)->count(),
            'new_users_today' => User::where('created_at', '>=', $today)->count(),
            'new_users_week' => User::where('created_at', '>=', $weekAgo)->count(),
        ];

        $retention = [
            'd1' => $this->calculateRetention(1),
            'd7' => $this->calculateRetention(7),
            'd30' => $this->calculateRetention(30),
        ];

        return view('admin.dashboard', compact('stats', 'retention'));
    }

    private function calculateRetention(int $days): float
    {
        $targetDate = Carbon::today()->subDays($days);
        $cohort = User::whereDate('created_at', $targetDate)->count();
        if ($cohort === 0) return 0;

        $returned = User::whereDate('created_at', $targetDate)
            ->where('last_activity', '>=', Carbon::today())
            ->count();

        return round(($returned / $cohort) * 100, 1);
    }
}
