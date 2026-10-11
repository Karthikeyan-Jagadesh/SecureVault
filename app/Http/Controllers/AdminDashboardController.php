<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FileModel;
use App\Models\MalwareScan;
use App\Models\QuarantineFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // System-wide metrics
        $totalUsers = User::count();
        $totalTenants = Tenant::count();

        $totalFiles = FileModel::withoutGlobalScopes()->count();
        $safeFiles = FileModel::withoutGlobalScopes()->where('scan_status', 'clean')->count();
        $maliciousFiles = FileModel::withoutGlobalScopes()->where('scan_status', 'malicious')->count();
        $quarantinedFiles = QuarantineFile::withoutGlobalScopes()->where('resolution_status', 'quarantined')->count();

        $totalQuotaBytes = Tenant::sum('storage_quota_bytes');
        $totalUsedBytes = Tenant::sum('storage_used_bytes');

        $storagePercentage = $totalQuotaBytes > 0 ? round(($totalUsedBytes / $totalQuotaBytes) * 100, 1) : 0;

        $recentSecurityEvents = AuditLog::withoutGlobalScopes()
            ->with(['tenant', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $scanStats = [
            'total_scans' => MalwareScan::withoutGlobalScopes()->count(),
            'clean_scans' => MalwareScan::withoutGlobalScopes()->where('scan_status', 'clean')->count(),
            'threats_detected' => MalwareScan::withoutGlobalScopes()->where('threat_found', true)->count(),
            'avg_duration_ms' => round(MalwareScan::withoutGlobalScopes()->avg('scan_duration_ms') ?: 0, 1),
        ];

        $recentScans = MalwareScan::withoutGlobalScopes()
            ->with(['file', 'tenant'])
            ->latest('scanned_at')
            ->take(8)
            ->get();

        $tenants = Tenant::withCount(['users', 'files'])->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalTenants',
            'totalFiles',
            'safeFiles',
            'maliciousFiles',
            'quarantinedFiles',
            'totalQuotaBytes',
            'totalUsedBytes',
            'storagePercentage',
            'recentSecurityEvents',
            'scanStats',
            'recentScans',
            'tenants'
        ));
    }
}
