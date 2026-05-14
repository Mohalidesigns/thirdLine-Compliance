<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AuditWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;

uses(RefreshDatabase::class);

function seedReferenceData(): void
{
    Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    Regulator::create(['code' => 'SEC', 'name' => 'Securities and Exchange Commission', 'country' => 'NGA']);
    InstrumentType::create(['name' => 'Act']);
    InstrumentType::create(['name' => 'Regulation']);
    Nature::create(['name' => 'Statutory']);
    Status::create(['name' => 'Active']);
    AreaOfFocus::create(['name' => 'AML/CFT']);
    RiskRating::create(['name' => 'High', 'color' => '#DD6B20']);
    RiskRating::create(['name' => 'Medium', 'color' => '#D4AF37']);
    RiskRating::create(['name' => 'Low', 'color' => '#2D7D46']);
}

function makeInstrumentData(array $overrides = []): array
{
    $refs = Regulator::first() ?? Regulator::create(['code' => 'CBN', 'name' => 'CBN', 'country' => 'NGA']);
    $type = InstrumentType::first() ?? InstrumentType::create(['name' => 'Act']);
    $nat  = Nature::first() ?? Nature::create(['name' => 'Statutory']);
    $stat = Status::first() ?? Status::create(['name' => 'Active']);
    $area = AreaOfFocus::first() ?? AreaOfFocus::create(['name' => 'AML/CFT']);
    $risk = RiskRating::first() ?? RiskRating::create(['name' => 'High', 'color' => '#DD6B20']);

    return array_merge([
        'source_title'       => 'Test Instrument',
        'regulator_id'       => $refs->id,
        'instrument_type_id' => $type->id,
        'nature_id'          => $nat->id,
        'status_id'          => $stat->id,
        'area_of_focus_id'   => $area->id,
        'risk_rating_id'     => $risk->id,
        'applicability'      => 'Yes',
    ], $overrides);
}

it('lists instruments with filters and returns correct prop shape', function () {
    seedReferenceData();
    $user = User::factory()->create();

    Instrument::create(makeInstrumentData(['source_title' => 'BOFIA 2020']));
    Instrument::create(makeInstrumentData(['source_title' => 'SEC Rules']));

    $response = $this->actingAs($user)->get('/instruments');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Instruments/Index')
        ->has('instruments')
        ->has('regulators')
        ->has('itemTypes')
        ->has('riskRatings')
    );
});

it('filters instruments by regulator code', function () {
    seedReferenceData();
    $user = User::factory()->create();
    $sec  = Regulator::where('code', 'SEC')->first();
    $type = InstrumentType::first();
    $nat  = Nature::first();
    $stat = Status::first();
    $area = AreaOfFocus::first();
    $risk = RiskRating::first();

    Instrument::create([
        'source_title' => 'CBN Instrument', 'regulator_id' => Regulator::where('code', 'CBN')->first()->id,
        'instrument_type_id' => $type->id, 'nature_id' => $nat->id, 'status_id' => $stat->id,
        'area_of_focus_id' => $area->id, 'risk_rating_id' => $risk->id, 'applicability' => 'Yes',
    ]);
    Instrument::create([
        'source_title' => 'SEC Instrument', 'regulator_id' => $sec->id,
        'instrument_type_id' => $type->id, 'nature_id' => $nat->id, 'status_id' => $stat->id,
        'area_of_focus_id' => $area->id, 'risk_rating_id' => $risk->id, 'applicability' => 'Yes',
    ]);

    $response = $this->actingAs($user)->get('/instruments?regulator=SEC');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Instruments/Index')
        ->where('instruments.total', 1)
        ->where('instruments.data.0.regulator', 'SEC')
    );
});

it('creates an instrument and emits an audit event', function () {
    seedReferenceData();
    $user = User::factory()->create();

    $data = makeInstrumentData(['source_title' => 'New Test Instrument']);

    $response = $this->actingAs($user)->post('/instruments', $data);

    $response->assertRedirect('/instruments');
    $this->assertDatabaseHas('instruments', ['source_title' => 'New Test Instrument']);
    $this->assertDatabaseHas('audit_events', ['action' => 'instrument.created']);
});

it('updates an instrument and emits an audit event', function () {
    seedReferenceData();
    $user = User::factory()->create();

    $instrument = Instrument::create(makeInstrumentData(['source_title' => 'Original Title']));

    $data = makeInstrumentData(['source_title' => 'Updated Title']);
    $response = $this->actingAs($user)->put("/instruments/{$instrument->id}", $data);

    $response->assertRedirect('/instruments');
    $this->assertDatabaseHas('instruments', ['source_title' => 'Updated Title']);
    $this->assertDatabaseHas('audit_events', ['action' => 'instrument.updated']);
});

it('propagates exception when audit write fails on create', function () {
    seedReferenceData();
    $user = User::factory()->create();

    $mock = Mockery::mock(AuditWriter::class);
    $mock->shouldReceive('record')
        ->andThrow(new RuntimeException('Audit failure'));
    app()->instance(AuditWriter::class, $mock);

    $data = makeInstrumentData(['source_title' => 'Should Fail Audit']);

    $this->withoutExceptionHandling();
    $this->expectException(RuntimeException::class);
    $this->actingAs($user)->post('/instruments', $data);
});

it('deletes an instrument and emits an audit event', function () {
    seedReferenceData();
    $user = User::factory()->create();

    $instrument = Instrument::create(makeInstrumentData());

    $response = $this->actingAs($user)->delete("/instruments/{$instrument->id}");

    $response->assertRedirect('/instruments');
    $this->assertSoftDeleted('instruments', ['id' => $instrument->id]);
    $this->assertDatabaseHas('audit_events', ['action' => 'instrument.deleted']);
});
