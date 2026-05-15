<?php

declare(strict_types=1);

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\StrategyRegistry;
use Modules\Returns\Strategies\CbnEfassStubStrategy;
use Modules\Returns\Strategies\ManualStubStrategy;
use Modules\Returns\Strategies\NfiuGoAmlStubStrategy;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
    Storage::fake('local');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeStrategyDef(array $overrides = []): ReturnDefinition
{
    return ReturnDefinition::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'code' => 'STRAT-'.uniqid(),
        'title' => 'Strategy Test Definition',
        'regulator' => 'nfiu',
        'submission_channel' => 'goaml_xml',
        'file_format' => 'xml',
        'frequency' => 'event_driven',
        'evidence_required' => false,
        'active' => true,
    ], $overrides));
}

function makeStrategyRun(ReturnDefinition $def, array $overrides = []): ReturnRun
{
    return ReturnRun::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => now()->format('Y-m'),
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'due_at' => now()->addDays(5),
        'status' => 'scheduled',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// NfiuGoAmlStubStrategy
// ---------------------------------------------------------------------------

it('NfiuGoAmlStubStrategy generates a payload at the expected path', function () {
    $def = makeStrategyDef(['regulator' => 'nfiu', 'submission_channel' => 'goaml_xml']);
    $run = makeStrategyRun($def);

    $strategy = new NfiuGoAmlStubStrategy;
    $path = $strategy->buildPayload($run);

    expect($path)->toBe("returns/{$run->id}/goaml.xml");
    Storage::assertExists($path);

    $contents = Storage::get($path);
    expect($contents)->toContain('<goAML>');
    expect($contents)->toContain($run->period_label);
});

it('NfiuGoAmlStubStrategy submit returns a fake NFIU reference', function () {
    $def = makeStrategyDef(['regulator' => 'nfiu', 'submission_channel' => 'goaml_xml']);
    $run = makeStrategyRun($def);

    $strategy = new NfiuGoAmlStubStrategy;
    $result = $strategy->submit($run);

    expect($result['reference'])->toStartWith('NFIU-');
    expect($result['submitted_at'])->toBeInstanceOf(Carbon::class);
    expect($result['acknowledged'])->toBeFalse();
});

it('NfiuGoAmlStubStrategy has correct regulator and channel codes', function () {
    $strategy = new NfiuGoAmlStubStrategy;
    expect($strategy->regulatorCode())->toBe('nfiu');
    expect($strategy->submissionChannel())->toBe('goaml_xml');
});

// ---------------------------------------------------------------------------
// CbnEfassStubStrategy
// ---------------------------------------------------------------------------

it('CbnEfassStubStrategy generates a CSV payload', function () {
    $def = makeStrategyDef(['regulator' => 'cbn', 'submission_channel' => 'cbn_efass', 'file_format' => 'xlsx']);
    $run = makeStrategyRun($def);

    $strategy = new CbnEfassStubStrategy;
    $path = $strategy->buildPayload($run);

    expect($path)->toBe("returns/{$run->id}/efass_stub.csv");
    Storage::assertExists($path);

    $contents = Storage::get($path);
    expect($contents)->toContain($run->period_label);
});

it('CbnEfassStubStrategy submit returns a fake eFASS reference', function () {
    $def = makeStrategyDef(['regulator' => 'cbn', 'submission_channel' => 'cbn_efass']);
    $run = makeStrategyRun($def);

    $strategy = new CbnEfassStubStrategy;
    $result = $strategy->submit($run);

    expect($result['reference'])->toStartWith('EFASS-');
    expect($result['acknowledged'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// ManualStubStrategy
// ---------------------------------------------------------------------------

it('ManualStubStrategy buildPayload returns null (no file)', function () {
    $def = makeStrategyDef(['regulator' => 'cbn', 'submission_channel' => 'manual']);
    $run = makeStrategyRun($def);

    $strategy = new ManualStubStrategy;
    $path = $strategy->buildPayload($run);

    expect($path)->toBeNull();
});

it('ManualStubStrategy submit is a no-op that records now', function () {
    $def = makeStrategyDef(['regulator' => 'cbn', 'submission_channel' => 'manual']);
    $run = makeStrategyRun($def);

    $strategy = new ManualStubStrategy;
    $result = $strategy->submit($run);

    expect($result['submitted_at'])->toBeInstanceOf(Carbon::class);
    expect($result['acknowledged'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// StrategyRegistry resolution
// ---------------------------------------------------------------------------

it('registry resolves NfiuGoAmlStubStrategy for nfiu:goaml_xml', function () {
    $def = makeStrategyDef(['regulator' => 'nfiu', 'submission_channel' => 'goaml_xml']);

    $registry = app(StrategyRegistry::class);
    $strategy = $registry->resolve($def);

    expect($strategy)->toBeInstanceOf(NfiuGoAmlStubStrategy::class);
});

it('registry resolves CbnEfassStubStrategy for cbn:cbn_efass', function () {
    $def = makeStrategyDef(['regulator' => 'cbn', 'submission_channel' => 'cbn_efass']);

    $registry = app(StrategyRegistry::class);
    $strategy = $registry->resolve($def);

    expect($strategy)->toBeInstanceOf(CbnEfassStubStrategy::class);
});

it('registry falls back to ManualStubStrategy for portal channel', function () {
    $def = makeStrategyDef(['regulator' => 'sec', 'submission_channel' => 'portal']);

    $registry = app(StrategyRegistry::class);
    $strategy = $registry->resolve($def);

    expect($strategy)->toBeInstanceOf(ManualStubStrategy::class);
});

it('registry falls back to ManualStubStrategy for unknown regulator/channel', function () {
    $def = makeStrategyDef(['regulator' => 'other', 'submission_channel' => 'api']);

    $registry = app(StrategyRegistry::class);
    $strategy = $registry->resolve($def);

    expect($strategy)->toBeInstanceOf(ManualStubStrategy::class);
});
