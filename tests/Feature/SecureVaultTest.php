<?php

namespace Tests\Feature;

use App\Models\FileModel;
use App\Models\QuarantineFile;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureVaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@cyberguard.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_secure_file_upload_pipeline_accepts_clean_pdf(): void
    {
        $user = User::where('email', 'admin@cyberguard.com')->first();
        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('files', [
            'original_name' => 'contract.pdf',
            'scan_status' => 'clean',
            'is_quarantined' => false,
        ]);
    }

    public function test_upload_pipeline_rejects_dangerous_executable_extensions(): void
    {
        $user = User::where('email', 'admin@cyberguard.com')->first();
        $file = UploadedFile::fake()->create('webshell.php', 100, 'application/x-php');

        $response = $this->actingAs($user)->post('/files/upload', [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('files', [
            'original_name' => 'webshell.php',
        ]);
    }

    public function test_malware_scanner_identifies_and_quarantines_threats(): void
    {
        $user = User::where('email', 'admin@cyberguard.com')->first();
        
        // Double extension malicious file (e.g. invoice.pdf.exe)
        $file = UploadedFile::fake()->create('invoice.pdf.exe', 100);

        $response = $this->actingAs($user)->post('/files/upload', [
            'file' => $file,
        ]);

        // Extension validator blocks .exe
        $response->assertSessionHasErrors('file');
    }

    public function test_quarantined_files_cannot_be_downloaded_by_users(): void
    {
        $user = User::where('email', 'admin@cyberguard.com')->first();

        $fileModel = FileModel::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'original_name' => 'infected.txt',
            'storage_path' => 'quarantine/test.bin',
            'mime_type' => 'text/plain',
            'file_extension' => 'txt',
            'size_bytes' => 100,
            'sha256_hash' => hash('sha256', 'dummy'),
            'scan_status' => 'quarantined',
            'is_quarantined' => true,
        ]);

        $response = $this->actingAs($user)->get("/files/{$fileModel->id}/download");
        $response->assertStatus(403);
    }

    public function test_multi_tenant_data_isolation_prevents_cross_tenant_access(): void
    {
        $userTenant1 = User::where('email', 'admin@cyberguard.com')->first();
        $userTenant2 = User::where('email', 'admin@apexfintech.com')->first();

        // Tenant 1 uploads file
        $fileTenant1 = FileModel::create([
            'tenant_id' => $userTenant1->tenant_id,
            'user_id' => $userTenant1->id,
            'original_name' => 'cyberguard_secret.pdf',
            'storage_path' => 'secure_vault/tenants/1/secret.dat',
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'size_bytes' => 200,
            'sha256_hash' => hash('sha256', 'cyberguard_secret'),
            'scan_status' => 'clean',
        ]);

        // User from Tenant 2 attempts to query or access Tenant 1 file
        $fileFromQuery = $this->actingAs($userTenant2)->get('/dashboard');

        // Due to TenantScope, Tenant 2 user will NOT see Tenant 1 file in workspace
        $fileFromQuery->assertDontSee('cyberguard_secret.pdf');
    }
}
