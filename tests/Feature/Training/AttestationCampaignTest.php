<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\AttestationCampaign;
use Modules\Training\Models\AttestationRecord;
use Modules\Training\Services\AttestationService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeCampaign(array $overrides = []): AttestationCampaign
{
    return AttestationCampaign::create(array_merge([
        'code' => 'ATT-TEST-'.uniqid(),
        'title' => 'Test Campaign',
        'body' => 'I agree to the terms.',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonths(2),
        'mandatory_for_roles' => ['compliance_officer', 'control_tester'],
        'status' => 'active',
    ], $overrides));
}

function makeUserForAttestation(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ─── Sign flow ────────────────────────────────────────────────────────────────

it('signCampaign records a signature with ip and user_agent', function () {
    $service = app(AttestationService::class);
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    $request = Request::create('/my/attestations/sign', 'POST');

    $record = $service->signCampaign($campaign, $user, $request);

    expect($record)->toBeInstanceOf(AttestationRecord::class);
    expect($record->campaign_id)->toBe($campaign->id);
    expect($record->user_id)->toBe($user->id);
    expect($record->signed_at)->not->toBeNull();
    expect($record->ip)->not->toBeNull();
});

it('signCampaign is idempotent — second call returns existing record', function () {
    $service = app(AttestationService::class);
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    $request = Request::create('/my/attestations/sign', 'POST');

    $first = $service->signCampaign($campaign, $user, $request);
    $second = $service->signCampaign($campaign, $user, $request);

    expect($first->id)->toBe($second->id);
    expect(AttestationRecord::count())->toBe(1);
});

it('cannot sign a closed campaign via the HTTP endpoint', function () {
    $campaign = makeCampaign(['status' => 'closed']);
    $user = makeUserForAttestation('compliance_officer');

    $this->actingAs($user)
        ->post("/my/attestations/{$campaign->id}/sign", ['confirmation' => '1'])
        ->assertStatus(422);
});

it('signing without confirmation returns validation error', function () {
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    $this->actingAs($user)
        ->post("/my/attestations/{$campaign->id}/sign", [])
        ->assertSessionHasErrors('confirmation');
});

it('signing with confirmation redirects and creates record', function () {
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    $this->actingAs($user)
        ->post("/my/attestations/{$campaign->id}/sign", ['confirmation' => '1'])
        ->assertRedirect(route('my.attestations.index'));

    expect(AttestationRecord::where('campaign_id', $campaign->id)
        ->where('user_id', $user->id)
        ->exists())->toBeTrue();
});

// ─── pendingForUser ───────────────────────────────────────────────────────────

it('pendingForUser returns active campaigns that user is required for and has not signed', function () {
    $service = app(AttestationService::class);

    $campaign = makeCampaign([
        'mandatory_for_roles' => ['compliance_officer'],
        'status' => 'active',
    ]);

    $officer = makeUserForAttestation('compliance_officer');
    $tester = makeUserForAttestation('control_tester');

    // Tester is not in the mandatory_for_roles.
    $pendingForTester = $service->pendingForUser($tester);
    expect($pendingForTester)->toHaveCount(0);

    // Officer is required but hasn't signed yet.
    $pendingForOfficer = $service->pendingForUser($officer);
    expect($pendingForOfficer)->toHaveCount(1);
    expect($pendingForOfficer->first()->id)->toBe($campaign->id);

    // After signing, pendingForUser should return 0.
    $request = Request::create('/sign', 'POST');
    $service->signCampaign($campaign, $officer, $request);
    expect($service->pendingForUser($officer))->toHaveCount(0);
});

// ─── hasSigned ───────────────────────────────────────────────────────────────

it('hasSigned returns false before signing and true after', function () {
    $service = app(AttestationService::class);
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    expect($campaign->hasSigned($user))->toBeFalse();

    $request = Request::create('/sign', 'POST');
    $service->signCampaign($campaign, $user, $request);

    expect($campaign->hasSigned($user))->toBeTrue();
});

// ─── Audit event ─────────────────────────────────────────────────────────────

it('signing a campaign writes an audit event', function () {
    $service = app(AttestationService::class);
    $campaign = makeCampaign();
    $user = makeUserForAttestation('compliance_officer');

    $auditBefore = DB::table('audit_events')
        ->where('action', 'attestation_record.created')
        ->count();

    $request = Request::create('/sign', 'POST');
    $service->signCampaign($campaign, $user, $request);

    $auditAfter = DB::table('audit_events')
        ->where('action', 'attestation_record.created')
        ->count();

    expect($auditAfter - $auditBefore)->toBe(1);
});
