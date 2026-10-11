<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FileModel;
use App\Models\QuarantineFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveSecurityStreamController extends Controller
{
    /**
     * Stream real-time security events to administrator dashboard using Server-Sent Events (SSE).
     */
    public function stream(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            $lastCheckedId = AuditLog::withoutGlobalScopes()->max('id') ?: 0;

            // Send initial ping event
            echo "event: ping\n";
            echo 'data: {"status":"connected","time":"' . date('H:i:s') . '"}' . "\n\n";
            ob_flush();
            flush();

            for ($i = 0; $i < 30; $i++) {
                if (connection_aborted()) break;

                // Check for new audit log events since last check
                $newLogs = AuditLog::withoutGlobalScopes()
                    ->with(['tenant', 'user'])
                    ->where('id', '>', $lastCheckedId)
                    ->get();

                foreach ($newLogs as $log) {
                    $lastCheckedId = max($lastCheckedId, $log->id);

                    $payload = json_encode([
                        'id' => $log->id,
                        'action' => $log->action,
                        'category' => $log->event_category,
                        'severity' => $log->severity,
                        'tenant' => $log->tenant ? $log->tenant->name : 'System',
                        'user' => $log->user ? $log->user->name : 'System/Guest',
                        'ip' => $log->ip_address,
                        'metadata' => $log->metadata,
                        'time' => $log->created_at->diffForHumans(),
                    ]);

                    echo "event: security_log\n";
                    echo "data: {$payload}\n\n";
                    ob_flush();
                    flush();
                }

                // Push current real-time system stats
                $stats = json_encode([
                    'total_files' => FileModel::withoutGlobalScopes()->count(),
                    'quarantined_files' => QuarantineFile::withoutGlobalScopes()->where('resolution_status', 'quarantined')->count(),
                    'safe_files' => FileModel::withoutGlobalScopes()->where('scan_status', 'clean')->count(),
                    'malicious_files' => FileModel::withoutGlobalScopes()->where('scan_status', 'malicious')->count(),
                    'timestamp' => date('H:i:s'),
                ]);

                echo "event: stats_update\n";
                echo "data: {$stats}\n\n";
                ob_flush();
                flush();

                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
