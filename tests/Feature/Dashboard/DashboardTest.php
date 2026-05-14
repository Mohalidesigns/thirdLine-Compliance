<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Obligation;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;
use Modules\Sanctkb\Models\Sanction;

uses(RefreshDatabase::class);

function seedDashboardData(): array
{
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $reg2 = Regulator::create(['code' => 'SEC', 'name' => 'Securities and Exchange Commission', 'country' => 'NGA']);
    $type = InstrumentType::create(['name' => 'Act']);
    $nat  = Nature::create(['name' => 'Statutory']);
    $stat = Status::create(['name' => 'Active']);
    $area = AreaOfFocus::create(['name' => 'AML/CFT']);
    $high = RiskRating::create(['name' => 'High', 'color' => '#DD6B20']);
    $med  = RiskRating::create(['name' => 'Medium', 'color' => '#D4AF37']);

    $i1 = Instrument::create([
        'source_title' => 'BOFIA', 'regulator_id' => $reg->id,
        'instrument_type_id' => $type->id, 'nature_id' => $nat->id,
        'status_id' => $stat->id, 'area_of_focus_id' => $area->id,
        'risk_rating_id' => $high->id, 'applicability' => 'Yes',
    ]);
    $i2 = Instrument::create([
        'source_title' => 'SEC Rules', 'regulator_id' => $reg2->id,
        'instrument_type_id' => $type->id, 'nature_id' => $nat->id,
        'status_id' => $stat->id, 'area_of_focus_id' => $area->id,
        'risk_rating_id' => $med->id, 'applicability' => 'Yes',
    ]);

    Obligation::create([
        'instrument_id' => $i1->id, 'title' => 'Obl 1',
        'description' => 'desc', 'due_basis' => 'recurring',
        'next_due_date' => now()->addDays(10)->toDateString(), 'status' => 'open',
    ]);

    Sanction::create([
        'regulator_id' => $reg->id,
        'offence'      => 'AML failure',
        'party_name'   => 'Bank A',
        'party_type'   => 'institution',
        'penalty_type' => 'monetary',
        'amount_naira' => 50_000_000,
        'effective_date' => '2024-09-15',
    ]);

    return [$i1, $i2, $reg, $reg2];
}

it('returns 4 KPI counts matching seeded data', function () {
    seedDashboardData();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('stats')
        ->where('stats.instruments', 2)
        ->where('stats.obligations', 1)
        ->where('stats.sanctions', 1)
    );
});

it('returns 4 pivots with non-empty entries', function () {
    seedDashboardData();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('byRegulator')
        ->has('byNature')
        ->has('byRisk')
        ->has('upcoming')
    );
});

it('returns byRegulator pivot with instrument counts per regulator', function () {
    seedDashboardData();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('byRegulator.0.name')
        ->has('byRegulator.0.count')
    );
});
