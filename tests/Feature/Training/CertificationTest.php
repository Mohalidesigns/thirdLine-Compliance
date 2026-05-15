<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Training\Models\Certification;
use Modules\Training\Services\CertificationService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeCertUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeCertification(User $user, array $overrides = []): Certification
{
    return Certification::create(array_merge([
        'user_id' => $user->id,
        'name' => 'ACAMS',
        'issuing_body' => 'ACAMS Global',
        'issued_at' => now()->subYear()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
        'status' => 'active',
    ], $overrides));
}

// ─── Expiry scan ──────────────────────────────────────────────────────────────

it('scanForExpiring classifies active cert correctly', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('compliance_officer');

    $cert = makeCertification($user, [
        'expires_at' => now()->addDays(90)->toDateString(),
        'status' => 'active',
    ]);

    $changed = $service->scanForExpiring();

    expect($cert->fresh()->status)->toBe('active');
    expect($changed)->toBe(0);
});

it('scanForExpiring classifies cert expiring in 45 days as expiring', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('compliance_officer');

    $cert = makeCertification($user, [
        'expires_at' => now()->addDays(45)->toDateString(),
        'status' => 'active', // currently marked active, should flip to expiring
    ]);

    $changed = $service->scanForExpiring();

    expect($cert->fresh()->status)->toBe('expiring');
    expect($changed)->toBe(1);
});

it('scanForExpiring classifies cert expired yesterday as expired', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('auditor');

    $cert = makeCertification($user, [
        'expires_at' => now()->subDay()->toDateString(),
        'status' => 'active',
    ]);

    $changed = $service->scanForExpiring();

    expect($cert->fresh()->status)->toBe('expired');
    expect($changed)->toBe(1);
});

it('scanForExpiring cert with no expiry stays active', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('compliance_officer');

    $cert = makeCertification($user, [
        'expires_at' => null,
        'status' => 'active',
    ]);

    $changed = $service->scanForExpiring();

    expect($cert->fresh()->status)->toBe('active');
    expect($changed)->toBe(0);
});

it('scanForExpiring returns correct count when multiple certs change status', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('compliance_officer');

    makeCertification($user, ['expires_at' => now()->addDays(30)->toDateString(), 'status' => 'active', 'name' => 'CFE']);
    makeCertification($user, ['expires_at' => now()->subDays(10)->toDateString(), 'status' => 'active', 'name' => 'CIPP']);

    $changed = $service->scanForExpiring();

    expect($changed)->toBe(2);
});

// ─── daysUntilExpiry helper ───────────────────────────────────────────────────

it('daysUntilExpiry returns positive value for future expiry', function () {
    $user = makeCertUser('compliance_officer');
    $cert = makeCertification($user, ['expires_at' => now()->addDays(30)->toDateString()]);

    $days = $cert->daysUntilExpiry();
    expect($days)->toBeGreaterThan(0);
    expect($days <= 30)->toBeTrue();
});

it('daysUntilExpiry returns negative value for expired cert', function () {
    $user = makeCertUser('auditor');
    $cert = makeCertification($user, ['expires_at' => now()->subDays(10)->toDateString()]);

    expect($cert->daysUntilExpiry())->toBeLessThan(0);
});

it('daysUntilExpiry returns null when no expiry date', function () {
    $user = makeCertUser('compliance_officer');
    $cert = makeCertification($user, ['expires_at' => null]);

    expect($cert->daysUntilExpiry())->toBeNull();
});

// ─── Authorization ────────────────────────────────────────────────────────────

it('user cannot view another users certification (403)', function () {
    $owner = makeCertUser('compliance_officer');
    $other = makeCertUser('risk_owner');

    $cert = makeCertification($owner);

    // risk_owner does not have certifications.view so cannot see someone else's cert.
    // The CertificationPolicy allows view only for the owner or users with certifications.view.
    // risk_owner has neither, so the gate should deny.
    $this->actingAs($other);
    $this->assertFalse($other->can('view', $cert));
});

it('compliance_officer can view any certification (has certifications.manage)', function () {
    $owner = makeCertUser('control_tester');
    $officer = makeCertUser('compliance_officer');

    $cert = makeCertification($owner);

    $this->actingAs($officer);
    // compliance_officer has certifications.manage so can view any cert.
    expect($officer->can('view', $cert))->toBeTrue();
});

it('user can view their own certification', function () {
    $owner = makeCertUser('risk_owner');
    $cert = makeCertification($owner);

    $this->actingAs($owner);
    expect($owner->can('view', $cert))->toBeTrue();
});

// ─── recordCertification ─────────────────────────────────────────────────────

it('recordCertification creates a certification for the user', function () {
    $service = app(CertificationService::class);
    $user = makeCertUser('compliance_officer');

    $cert = $service->recordCertification($user, [
        'name' => 'CIPP/E',
        'issuing_body' => 'IAPP',
        'issued_at' => now()->subMonths(6)->toDateString(),
        'expires_at' => now()->addMonths(18)->toDateString(),
        'status' => 'active',
    ]);

    expect($cert)->toBeInstanceOf(Certification::class);
    expect($cert->user_id)->toBe($user->id);
    expect($cert->name)->toBe('CIPP/E');
});
