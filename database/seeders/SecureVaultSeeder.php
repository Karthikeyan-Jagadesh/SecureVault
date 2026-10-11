<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecureVaultSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Global access across all tenants and security configurations.',
        ]);

        $tenantAdminRole = Role::create([
            'name' => 'tenant_admin',
            'display_name' => 'Tenant Administrator',
            'description' => 'Full administrative control over tenant users, storage, and security logs.',
        ]);

        $tenantUserRole = Role::create([
            'name' => 'tenant_user',
            'display_name' => 'Tenant User',
            'description' => 'Standard tenant user with permission to upload and manage personal files.',
        ]);

        // 2. Permissions
        $permissions = [
            'file.upload' => 'Upload Files',
            'file.download' => 'Download Files',
            'file.delete' => 'Delete Files',
            'file.share' => 'Share Files',
            'tenant.manage' => 'Manage Tenant',
            'quarantine.manage' => 'Manage Quarantine',
            'audit.view' => 'View Audit Logs',
        ];

        foreach ($permissions as $name => $displayName) {
            $perm = Permission::create(['name' => $name, 'display_name' => $displayName]);
            $superAdminRole->permissions()->attach($perm);
            $tenantAdminRole->permissions()->attach($perm);

            if (in_array($name, ['file.upload', 'file.download', 'file.delete', 'file.share'])) {
                $tenantUserRole->permissions()->attach($perm);
            }
        }

        // 3. Tenants
        $tenant1 = Tenant::create([
            'name' => 'CyberGuard Enterprise',
            'slug' => 'cyberguard',
            'storage_quota_bytes' => 1073741824, // 1 GB
            'storage_used_bytes' => 0,
            'status' => 'active',
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Apex Financial Tech',
            'slug' => 'apex-fintech',
            'storage_quota_bytes' => 524288000, // 500 MB
            'storage_used_bytes' => 0,
            'status' => 'active',
        ]);

        // 4. Users
        $superUser = User::create([
            'tenant_id' => null,
            'role_id' => $superAdminRole->id,
            'name' => 'Global Security Admin',
            'email' => 'superadmin@securevault.local',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $cyberAdmin = User::create([
            'tenant_id' => $tenant1->id,
            'role_id' => $tenantAdminRole->id,
            'name' => 'Sarah Connor (Security Lead)',
            'email' => 'admin@cyberguard.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $cyberUser = User::create([
            'tenant_id' => $tenant1->id,
            'role_id' => $tenantUserRole->id,
            'name' => 'John Doe (Analyst)',
            'email' => 'user@cyberguard.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $apexAdmin = User::create([
            'tenant_id' => $tenant2->id,
            'role_id' => $tenantAdminRole->id,
            'name' => 'Alex Mercer (Apex Lead)',
            'email' => 'admin@apexfintech.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        // 5. Default Root Folders
        Folder::create([
            'tenant_id' => $tenant1->id,
            'user_id' => $cyberAdmin->id,
            'name' => 'Confidential Reports',
            'path' => '/Confidential Reports',
        ]);

        Folder::create([
            'tenant_id' => $tenant1->id,
            'user_id' => $cyberAdmin->id,
            'name' => 'Project Archives',
            'path' => '/Project Archives',
        ]);

        Folder::create([
            'tenant_id' => $tenant2->id,
            'user_id' => $apexAdmin->id,
            'name' => 'Financial Statements',
            'path' => '/Financial Statements',
        ]);

        // 6. Initial Audit Log Entries
        AuditLog::create([
            'tenant_id' => null,
            'user_id' => $superUser->id,
            'action' => 'SYSTEM_INIT',
            'event_category' => 'system',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SecureVault System Bootstrapper',
            'resource_type' => 'System',
            'resource_id' => 1,
            'metadata' => ['event' => 'SecureVault Security Engine Initialized'],
            'severity' => 'info',
        ]);

        AuditLog::create([
            'tenant_id' => $tenant1->id,
            'user_id' => $cyberAdmin->id,
            'action' => 'TENANT_PROVISIONED',
            'event_category' => 'admin',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0)',
            'resource_type' => 'Tenant',
            'resource_id' => $tenant1->id,
            'metadata' => ['tenant_name' => 'CyberGuard Enterprise', 'quota' => '1 GB'],
            'severity' => 'info',
        ]);
    }
}
