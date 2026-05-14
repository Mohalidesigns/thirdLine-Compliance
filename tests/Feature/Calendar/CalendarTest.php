<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Obligation;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;

uses(RefreshDatabase::class);

function seedCalendarData(): Instrument
{
    $reg  = Regulator::create(['code' => 'CBN', 'name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    $type = InstrumentType::create(['name' => 'Act']);
    $nat  = Nature::create(['name' => 'Statutory']);
    $stat = Status::create(['name' => 'Active']);
    $area = AreaOfFocus::create(['name' => 'AML/CFT']);
    $risk = RiskRating::create(['name' => 'High', 'color' => '#DD6B20']);

    return Instrument::create([
        'source_title'       => 'BOFIA 2020',
        'regulator_id'       => $reg->id,
        'instrument_type_id' => $type->id,
        'nature_id'          => $nat->id,
        'status_id'          => $stat->id,
        'area_of_focus_id'   => $area->id,
        'risk_rating_id'     => $risk->id,
        'applicability'      => 'Yes',
    ]);
}

it('returns next 90 days of events sorted by date', function () {
    $instrument = seedCalendarData();
    $user = User::factory()->create();

    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'Monthly AML return',
        'description'   => 'Submit monthly AML return',
        'due_basis'     => 'recurring',
        'frequency'     => 'monthly',
        'next_due_date' => now()->addDays(5)->toDateString(),
        'status'        => 'open',
    ]);
    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'Quarterly return',
        'description'   => 'Submit quarterly return',
        'due_basis'     => 'recurring',
        'frequency'     => 'quarterly',
        'next_due_date' => now()->addDays(30)->toDateString(),
        'status'        => 'open',
    ]);
    // This one is outside the 90-day window
    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'Annual return',
        'description'   => 'Submit annual return',
        'due_basis'     => 'recurring',
        'frequency'     => 'annual',
        'next_due_date' => now()->addDays(120)->toDateString(),
        'status'        => 'open',
    ]);

    $response = $this->actingAs($user)->get('/calendar');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Calendar/Index')
        ->has('events', 2)
    );
});

it('returns calendar with correct event shape', function () {
    $instrument = seedCalendarData();
    $user = User::factory()->create();

    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'Test Obligation',
        'description'   => 'Test description',
        'due_basis'     => 'recurring',
        'next_due_date' => now()->addDays(10)->toDateString(),
        'status'        => 'open',
    ]);

    $response = $this->actingAs($user)->get('/calendar');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('events.0.id')
        ->has('events.0.due_date')
        ->has('events.0.obligation')
        ->has('events.0.instrument')
        ->has('events.0.status')
        ->has('events.0.days_until')
    );
});

it('signed ICS URL returns ICS body', function () {
    $instrument = seedCalendarData();
    $user = User::factory()->create();

    Obligation::create([
        'instrument_id' => $instrument->id,
        'title'         => 'ICS Test Obligation',
        'description'   => 'ICS desc',
        'due_basis'     => 'recurring',
        'next_due_date' => now()->addDays(15)->toDateString(),
        'status'        => 'open',
    ]);

    $signedUrl = URL::signedRoute('calendar.ics');

    $response = $this->actingAs($user)->get($signedUrl);

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
    $this->assertStringContainsString('BEGIN:VCALENDAR', $response->content());
    $this->assertStringContainsString('BEGIN:VEVENT', $response->content());
    $this->assertStringContainsString('ICS Test Obligation', $response->content());
});
