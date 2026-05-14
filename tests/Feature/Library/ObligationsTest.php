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

uses(RefreshDatabase::class);

function seedRefDataObl(): array
{
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $type = InstrumentType::create(['name' => 'Act']);
    $nat  = Nature::create(['name' => 'Statutory']);
    $stat = Status::create(['name' => 'Active']);
    $area = AreaOfFocus::create(['name' => 'AML/CFT']);
    $risk = RiskRating::create(['name' => 'High', 'color' => '#DD6B20']);

    $instrument = Instrument::create([
        'source_title'       => 'BOFIA 2020',
        'regulator_id'       => $reg->id,
        'instrument_type_id' => $type->id,
        'nature_id'          => $nat->id,
        'status_id'          => $stat->id,
        'area_of_focus_id'   => $area->id,
        'risk_rating_id'     => $risk->id,
        'applicability'      => 'Yes',
    ]);

    return [$instrument];
}

it('lists obligations linked to an instrument', function () {
    [$instrument] = seedRefDataObl();
    $user = User::factory()->create();

    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'Test Obligation',
        'description'   => 'Monthly return submission',
        'due_basis'     => 'recurring',
        'frequency'     => 'monthly',
        'status'        => 'open',
    ]);

    $response = $this->actingAs($user)->get('/obligations');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Obligations/Index')
        ->has('obligations.data', 1)
    );
});

it('creates an obligation under an instrument', function () {
    [$instrument] = seedRefDataObl();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/obligations', [
        'instrument_id' => $instrument->id,
        'title'         => 'New Obligation',
        'description'   => 'Annual filing requirement',
        'due_basis'     => 'recurring',
        'frequency'     => 'annual',
        'status'        => 'open',
    ]);

    $response->assertRedirect('/obligations');
    $this->assertDatabaseHas('obligations', [
        'title'         => 'New Obligation',
        'instrument_id' => $instrument->id,
    ]);
    $this->assertDatabaseHas('audit_events', ['action' => 'obligation.created']);
});

it('filters obligations by status', function () {
    [$instrument] = seedRefDataObl();
    $user = User::factory()->create();

    Obligation::create([
        'instrument_id' => $instrument->id, 'title' => 'Open Obligation',
        'description' => 'desc', 'due_basis' => 'recurring', 'status' => 'open',
    ]);
    Obligation::create([
        'instrument_id' => $instrument->id, 'title' => 'Satisfied Obligation',
        'description' => 'desc', 'due_basis' => 'one_off', 'status' => 'satisfied',
    ]);

    $response = $this->actingAs($user)->get('/obligations?status=open');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('obligations.total', 1)
    );
});
