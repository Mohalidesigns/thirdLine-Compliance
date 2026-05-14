<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Library\Jobs\BulkImportInstrumentsJob;

uses(RefreshDatabase::class);

it('accepts XLSX upload and dispatches import job', function (): void {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('instruments.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $response = $this->actingAs($user)->post('/instruments/bulk-import', [
        'file' => $file,
    ]);

    $response->assertRedirect('/instruments');

    Queue::assertPushed(BulkImportInstrumentsJob::class);

    Storage::disk('local')->assertExists('imports/'.$file->hashName());
});

it('rejects non-spreadsheet files on bulk-import', function (): void {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

    $response = $this->actingAs($user)->post('/instruments/bulk-import', [
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');

    Queue::assertNotPushed(BulkImportInstrumentsJob::class);
});

it('rejects bulk-import from unauthenticated users', function (): void {
    Queue::fake();

    $file = UploadedFile::fake()->create('instruments.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $response = $this->post('/instruments/bulk-import', [
        'file' => $file,
    ]);

    $response->assertRedirect('/login');

    Queue::assertNotPushed(BulkImportInstrumentsJob::class);
});

it('emits instrument.bulk_import_started audit event on upload', function (): void {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('instruments.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($user)->post('/instruments/bulk-import', [
        'file' => $file,
    ]);

    $this->assertDatabaseHas('audit_events', [
        'action' => 'instrument.bulk_import_started',
    ]);
});
