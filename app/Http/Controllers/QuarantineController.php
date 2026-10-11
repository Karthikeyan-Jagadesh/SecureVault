<?php

namespace App\Http\Controllers;

use App\Models\QuarantineFile;
use App\Services\AuditLogService;
use App\Services\QuarantineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuarantineController extends Controller
{
    public function __construct(private QuarantineService $quarantineService) {}

    public function index()
    {
        $quarantinedFiles = QuarantineFile::with(['file', 'tenant', 'quarantinedBy'])
            ->latest('quarantined_at')
            ->paginate(15);

        return view('admin.quarantine', compact('quarantinedFiles'));
    }

    public function restore(QuarantineFile $quarantine)
    {
        $adminUser = Auth::user();

        if (!$adminUser->isTenantAdmin()) {
            abort(403, 'Administrator security permissions required.');
        }

        $restored = $this->quarantineService->restoreFile($quarantine, $adminUser);

        if (!$restored) {
            return back()->withErrors(['quarantine' => 'Could not restore file from quarantine.']);
        }

        return back()->with('success', "Quarantined file '{$quarantine->original_filename}' restored to tenant storage as clean.");
    }

    public function purge(QuarantineFile $quarantine)
    {
        $adminUser = Auth::user();

        if (!$adminUser->isTenantAdmin()) {
            abort(403, 'Administrator security permissions required.');
        }

        $filename = $quarantine->original_filename;
        $this->quarantineService->purgeFile($quarantine, $adminUser);

        return back()->with('success', "Malicious file '{$filename}' permanently purged from quarantine vault.");
    }
}
