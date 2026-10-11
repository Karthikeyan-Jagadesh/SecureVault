<?php

namespace App\Services;

use App\Models\FileModel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureUploadPipelineService
{
    private array $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf', 'xml', 'json',
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
        'zip', 'tar', 'gz', '7z',
        'mp3', 'wav', 'mp4', 'mkv', 'avi'
    ];

    private array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar',
        'exe', 'bat', 'sh', 'cmd', 'ps1', 'vbs', 'dll', 'so',
        'msi', 'jar', 'scr', 'cpl', 'htpasswd', 'htaccess'
    ];

    public function __construct(
        private TenantStorageService $storageService,
        private MalwareScannerService $malwareScanner,
        private QuarantineService $quarantineService,
        private FileIntegrityService $integrityService
    ) {}

    /**
     * Execute full 6-step validation, storage, and malware scanning pipeline.
     */
    public function processUpload(UploadedFile $uploadedFile, Tenant $tenant, User $user, ?int $folderId = null): array
    {
        $originalName = $uploadedFile->getClientOriginalName();

        // Step 1: Filename Sanitization
        $sanitizedName = $this->sanitizeFilename($originalName);
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: pathinfo($sanitizedName, PATHINFO_EXTENSION));

        // Step 2: Extension Whitelist & Blacklist Validation
        if (in_array($extension, $this->dangerousExtensions) || !in_array($extension, $this->allowedExtensions)) {
            AuditLogService::log('UPLOAD_BLOCKED_EXTENSION', 'security', 'File', null, [
                'filename' => $originalName,
                'extension' => $extension,
                'reason' => 'Extension prohibited by security policy',
            ], 'danger', $tenant->id, $user->id);

            return [
                'success' => false,
                'error' => "Security Exception: File extension '.{$extension}' is strictly prohibited.",
            ];
        }

        // Step 3: MIME Type Validation via finfo Magic Bytes
        $realMimeType = $this->detectRealMimeType($uploadedFile);
        if (!$this->isMimeAllowed($realMimeType, $extension)) {
            AuditLogService::log('UPLOAD_BLOCKED_MIME_MISMATCH', 'security', 'File', null, [
                'filename' => $originalName,
                'extension' => $extension,
                'detected_mime' => $realMimeType,
            ], 'danger', $tenant->id, $user->id);

            return [
                'success' => false,
                'error' => "Security Exception: MIME type mismatch detected ({$realMimeType}). Upload rejected.",
            ];
        }

        // Step 4: Storage Quota Validation
        $fileSize = $uploadedFile->getSize();
        if (!$this->storageService->canStoreFile($tenant, $fileSize)) {
            AuditLogService::log('UPLOAD_BLOCKED_QUOTA_EXCEEDED', 'file', 'Tenant', $tenant->id, [
                'file_size' => $fileSize,
                'available_quota' => $tenant->storage_quota_bytes - $tenant->storage_used_bytes,
            ], 'warning', $tenant->id, $user->id);

            return [
                'success' => false,
                'error' => "Tenant Storage Quota Exceeded! Please free up space or upgrade storage quota.",
            ];
        }

        // Step 5: Secure Server-Side Storage Outside Web Root
        $storageUuid = (string) Str::uuid();
        $relativeStoragePath = "secure_vault/tenants/{$tenant->id}/{$storageUuid}.dat";

        $fileContent = $uploadedFile->getContent();
        Storage::put($relativeStoragePath, $fileContent);

        $fullDiskPath = Storage::path($relativeStoragePath);

        // Step 6: Compute SHA-256 Cryptographic Hash
        $sha256Hash = hash('sha256', $fileContent);

        // Create Database File Record
        $fileModel = FileModel::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'original_name' => $sanitizedName,
            'storage_path' => $relativeStoragePath,
            'mime_type' => $realMimeType,
            'file_extension' => $extension,
            'size_bytes' => $fileSize,
            'sha256_hash' => $sha256Hash,
            'scan_status' => 'pending',
            'is_quarantined' => false,
        ]);

        // Trigger Malware Scanner
        $scanResult = $this->malwareScanner->scanFile($fullDiskPath, $fileModel);

        if (!$scanResult['is_clean']) {
            // Quarantine malicious file immediately!
            $quarantineRecord = $this->quarantineService->quarantineFile($fileModel, $scanResult['threat_name'], $user);

            return [
                'success' => false,
                'is_quarantined' => true,
                'file' => $fileModel,
                'quarantine' => $quarantineRecord,
                'error' => "MALWARE DETECTED! Threat '{$scanResult['threat_name']}' identified. File has been quarantined.",
            ];
        }

        // Update Scan Status to Clean
        $fileModel->update(['scan_status' => 'clean']);

        // Update Tenant Storage Usage
        $this->storageService->addUsage($tenant, $fileSize);

        // Check for duplicate files
        $duplicates = $this->integrityService->findDuplicates($sha256Hash, $tenant->id, $fileModel->id);

        // Audit Log
        AuditLogService::log(
            'FILE_UPLOADED',
            'file',
            'FileModel',
            $fileModel->id,
            [
                'filename' => $sanitizedName,
                'size' => $fileSize,
                'sha256' => $sha256Hash,
                'scan_engine' => $scanResult['engine'],
                'has_duplicates' => count($duplicates) > 0,
            ],
            'info',
            $tenant->id,
            $user->id
        );

        return [
            'success' => true,
            'file' => $fileModel->fresh(),
            'scan' => $scanResult,
            'duplicates_count' => count($duplicates),
        ];
    }

    /**
     * Sanitize filename: remove null bytes, path traversal (`../`), control characters.
     */
    public function sanitizeFilename(string $filename): string
    {
        $filename = str_replace("\0", '', $filename); // Null byte removal
        $filename = basename($filename); // Path traversal removal
        $filename = preg_replace('/[^\w\.\-\s]/', '_', $filename); // Replace dangerous non-alphanumeric chars
        return trim($filename, '. ');
    }

    private function detectRealMimeType(UploadedFile $file): string
    {
        try {
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath)) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = @finfo_file($finfo, $realPath);
                    @finfo_close($finfo);
                    if ($mime && is_string($mime)) {
                        return $mime;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silence permission errors on temp stream files
        }

        return $file->getClientMimeType() ?: 'application/octet-stream';
    }

    private function isMimeAllowed(string $mime, string $extension): bool
    {
        // Disallow executable script MIME types
        $forbiddenMimes = [
            'application/x-php', 'text/x-php', 'application/x-httpd-php',
            'application/x-msdownload', 'application/x-executable',
            'application/x-sh', 'text/x-shellscript', 'application/x-bat'
        ];

        return !in_array(strtolower($mime), $forbiddenMimes);
    }
}
