<?php

namespace App\Http\Controllers\Admin;

use App\Blocks\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaFile;
use App\Models\Package;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserPackageAssignment;
use App\Services\Analytics\AnalyticsReporter;
use App\Services\Entitlements;
use App\Services\System\HealthCheck;
use App\Services\System\MaintenanceRunner;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Every number on this page is computed from the database; nothing is estimated or mocked. */
class DashboardController extends Controller
{
    public function index(Request $request, AnalyticsReporter $reporter, HealthCheck $health, BlockRegistry $blocks, Entitlements $entitlements, MaintenanceRunner $maintenance): View
    {
        // Fallback for hosts without cron: light housekeeping when staff open the dashboard.
        rescue(fn () => $maintenance->runIfDue(), report: true);

        [$todayStart, $todayEnd] = $reporter->range(now()->toDateString(), now()->toDateString(), 0);
        [$monthStart, $monthEnd] = $reporter->range(null, null, 30);
        $monthOverview = $reporter->overview(Profile::query()->pluck('id')->all(), $monthStart, $monthEnd);

        $currentByPackage = UserPackageAssignment::query()->current()
            ->selectRaw('package_id, COUNT(DISTINCT user_id) as total')->groupBy('package_id')->pluck('total', 'package_id');
        $assignedUsers = UserPackageAssignment::query()->current()->distinct()->count('user_id');
        $totalUsers = User::query()->count();
        $default = $entitlements->defaultPackage();

        $byPackage = Package::query()->orderBy('sort_order')->get()->map(fn (Package $p) => [
            'name' => $p->translate('name'),
            'total' => (int) ($currentByPackage[$p->id] ?? 0) + ($default && $default->id === $p->id ? max(0, $totalUsers - $assignedUsers) : 0),
        ])->filter(fn ($row) => $row['total'] > 0)->values();

        return view('admin.dashboard', [
            'kpi' => [
                'users_total' => $totalUsers,
                'users_active' => User::query()->where('status', User::STATUS_ACTIVE)->whereNull('deletion_requested_at')->count(),
                'users_active_30d' => User::query()->where('last_login_at', '>=', now()->subDays(30))->count(),
                'users_new_7d' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'users_new_30d' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'users_suspended' => User::query()->where('status', User::STATUS_SUSPENDED)->count(),
                'profiles_published' => Profile::query()->where('status', Profile::STATUS_PUBLISHED)->count(),
                'profiles_draft' => Profile::query()->where('status', Profile::STATUS_DRAFT)->count(),
                'links_total' => ProfileBlock::query()->whereIn('type', $blocks->linkTypes())->count(),
                'storage_bytes' => (int) MediaFile::query()->sum('size'),
                'disk_free' => @disk_free_space(storage_path()) ?: null,
            ],
            'today' => $reporter->platformTotals($todayStart, $todayEnd),
            'month' => $monthOverview['totals'],
            'series' => $monthOverview['series'],
            'byPackage' => $byPackage,
            'topProfiles' => $reporter->topProfiles($monthStart, $monthEnd, 5),
            'health' => $health->basic(),
            'audit' => AuditLog::query()->with('actor:id,name,username')->latest('id')->limit(8)->get(),
            'securityEvents' => SecurityEvent::query()->with('user:id,name,username')->whereIn('type', ['login_failed', 'two_factor_failed', 'lockout', 'account_suspended', 'password_reset'])->latest('id')->limit(8)->get(),
        ]);
    }
}
