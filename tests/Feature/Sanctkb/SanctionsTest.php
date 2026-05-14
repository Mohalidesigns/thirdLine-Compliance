<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Library\Models\Regulator;
use Modules\Sanctkb\Models\Sanction;

uses(RefreshDatabase::class);

function makeSanctionData(Regulator $regulator, array $overrides = []): array
{
    return array_merge([
        'regulator_id'   => $regulator->id,
        'offence'        => 'Failure to report suspicious transactions within 24 hours',
        'party_name'     => 'Bank A',
        'party_type'     => 'institution',
        'amount_naira'   => 50_000_000,
        'penalty_type'   => 'monetary',
        'effective_date' => '2024-09-15',
    ], $overrides);
}

it('lists sanctions and returns correct Inertia props', function () {
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $user = User::factory()->create();

    Sanction::create(makeSanctionData($reg));
    Sanction::create(makeSanctionData($reg, ['party_name' => 'Bank B', 'offence' => 'Capital adequacy breach']));

    $response = $this->actingAs($user)->get('/sanctions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Sanctions/Index')
        ->has('sanctions')
        ->has('categories')
        ->has('severities')
    );
});

it('paginates 25 sanctions per page', function () {
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $user = User::factory()->create();

    for ($i = 1; $i <= 30; $i++) {
        Sanction::create(makeSanctionData($reg, ['party_name' => "Bank {$i}"]));
    }

    $response = $this->actingAs($user)->get('/sanctions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('sanctions.per_page', 25)
        ->where('sanctions.total', 30)
    );
});

it('full-text searches sanctions by content via SQLite fallback', function () {
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $user = User::factory()->create();

    Sanction::create(makeSanctionData($reg, [
        'offence'    => 'Failure to conduct customer due diligence for PEP customers',
        'party_name' => 'Bank Alpha',
    ]));
    Sanction::create(makeSanctionData($reg, [
        'offence'    => 'Capital adequacy ratio breach',
        'party_name' => 'Bank Beta',
    ]));

    $response = $this->actingAs($user)->get('/sanctions?search=customer+due+diligence');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Sanctions/Index')
        ->where('sanctions.total', 1)
        ->where('sanctions.data.0.description', fn ($desc) => str_contains($desc, 'customer due diligence'))
    );
});

it('creates a sanction and emits an audit event', function () {
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/sanctions', makeSanctionData($reg));

    $response->assertRedirect('/sanctions');
    $this->assertDatabaseHas('sanctions', ['party_name' => 'Bank A']);
    $this->assertDatabaseHas('audit_events', ['action' => 'sanction.created']);
});
