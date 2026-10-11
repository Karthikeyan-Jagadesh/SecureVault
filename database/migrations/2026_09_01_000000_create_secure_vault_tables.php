<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tenants Table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->bigInteger('storage_quota_bytes')->default(524288000); // Default 500 MB
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();
        });

        // 2. Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        // 4. Permission Role Pivot Table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

        // 5. Modify Users Table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->after('tenant_id')->constrained('roles')->onDelete('set null');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        // 6. Folders Table
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->string('name');
            $table->string('path')->default('/');
            $table->timestamps();
        });

        // 7. Files Table
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->string('file_extension', 20);
            $table->bigInteger('size_bytes');
            $table->string('sha256_hash', 64);
            $table->enum('scan_status', ['pending', 'clean', 'malicious', 'quarantined', 'error'])->default('pending');
            $table->boolean('is_quarantined')->default(false);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. File Permissions Table
        Schema::create('file_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission_type', ['read', 'download', 'edit', 'delete'])->default('read');
            $table->timestamps();
        });

        // 9. Malware Scans Table
        Schema::create('malware_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('scanner_engine')->default('ClamAV / Heuristic Engine');
            $table->enum('scan_status', ['clean', 'malicious', 'error'])->default('clean');
            $table->boolean('threat_found')->default(false);
            $table->string('threat_name')->nullable();
            $table->integer('scan_duration_ms')->default(0);
            $table->json('details')->nullable();
            $table->timestamp('scanned_at');
        });

        // 10. Quarantine Files Table
        Schema::create('quarantine_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->constrained('files')->onDelete('set null');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('quarantine_path');
            $table->string('threat_name');
            $table->foreignId('quarantined_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('resolution_status', ['quarantined', 'restored', 'deleted'])->default('quarantined');
            $table->timestamp('quarantined_at');
            $table->timestamps();
        });

        // 11. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->string('event_category');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('severity', ['info', 'warning', 'danger', 'critical'])->default('info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('quarantine_files');
        Schema::dropIfExists('malware_scans');
        Schema::dropIfExists('file_permissions');
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['tenant_id', 'role_id', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenants');
    }
};
