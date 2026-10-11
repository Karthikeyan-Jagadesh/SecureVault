<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::withoutGlobalScopes()->with(['tenant', 'user']);

        if ($request->filled('category')) {
            $query->where('event_category', $request->input('category'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                    ->orWhere('ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('resource_type', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(25);

        return view('admin.audit_logs', compact('logs'));
    }
}
