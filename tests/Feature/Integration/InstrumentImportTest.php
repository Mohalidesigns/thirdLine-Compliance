<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Library\Imports\InstrumentImport;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

/**
 * Generate an in-memory XLSX fixture with the given rows.
 * Returns a path to a temp file that is cleaned up after the test.
 *
 * @param  array<int, array<string, string>>  $rows
 */
function buildXlsxFixture(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Header row (must match InstrumentImport expected heading keys)
    $headers = [
        'source_title',
        'regulator_code',
        'instrument_type',
        'nature',
        'status',
        'area_of_focus',
        'risk_rating',
        'applicability',
        'objectives',
        'date_issue',
        'date_commence',
        'link_url',
    ];

    // Write header row (row 1)
    foreach ($headers as $col => $header) {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
        $sheet->setCellValue("{$letter}1", $header);
    }

    // Write data rows starting from row 2
    foreach ($rows as $rowIdx => $row) {
        $rowNum = $rowIdx + 2; // +2 because row 1 is the header
        foreach ($headers as $col => $key) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue("{$letter}{$rowNum}", $row[$key] ?? '');
        }
    }

    $path = sys_get_temp_dir().'/instruments_test_'.uniqid().'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($path);

    return $path;
}

function importRefData(): void
{
    Regulator::firstOrCreate(['code' => 'CBN'], ['name' => 'Central Bank of Nigeria', 'country' => 'NGA']);
    Regulator::firstOrCreate(['code' => 'SEC'], ['name' => 'Securities and Exchange Commission', 'country' => 'NGA']);
    InstrumentType::firstOrCreate(['name' => 'Act']);
    InstrumentType::firstOrCreate(['name' => 'Regulation']);
    Nature::firstOrCreate(['name' => 'Statutory']);
    Status::firstOrCreate(['name' => 'Active']);
    AreaOfFocus::firstOrCreate(['name' => 'AML/CFT']);
    RiskRating::firstOrCreate(['name' => 'High'], ['color' => '#DD6B20']);
    RiskRating::firstOrCreate(['name' => 'Medium'], ['color' => '#D4AF37']);
    RiskRating::firstOrCreate(['name' => 'Low'], ['color' => '#2D7D46']);
}

function makeImportRows(int $count = 5): array
{
    $rows = [];
    for ($i = 1; $i <= $count; $i++) {
        $rows[] = [
            'source_title'    => "Import Test Instrument {$i}",
            'regulator_code'  => 'CBN',
            'instrument_type' => 'Act',
            'nature'          => 'Statutory',
            'status'          => 'Active',
            'area_of_focus'   => 'AML/CFT',
            'risk_rating'     => 'High',
            'applicability'   => 'Yes',
            'objectives'      => "Objectives for instrument {$i}",
            'date_issue'      => '2020-01-01',
            'date_commence'   => '2020-06-01',
            'link_url'        => '',
        ];
    }

    return $rows;
}

// ---------------------------------------------------------------------------

it('imports 5 instruments from XLSX and all 5 appear in the instruments table', function (): void {
    importRefData();

    $path = buildXlsxFixture(makeImportRows(5));

    try {
        $initialCount = Instrument::count();

        Excel::import(new InstrumentImport, $path);

        expect(Instrument::count())->toBe($initialCount + 5);

        for ($i = 1; $i <= 5; $i++) {
            $this->assertDatabaseHas('instruments', ['source_title' => "Import Test Instrument {$i}"]);
        }
    } finally {
        @unlink($path);
    }
});

it('re-importing the same XLSX creates no duplicate instruments (idempotent by source_title)', function (): void {
    importRefData();

    $rows = makeImportRows(5);
    $path = buildXlsxFixture($rows);

    try {
        Excel::import(new InstrumentImport, $path);
        $countAfterFirst = Instrument::count();

        // Import same file again
        Excel::import(new InstrumentImport, $path);
        $countAfterSecond = Instrument::count();

        expect($countAfterSecond)->toBe($countAfterFirst,
            'Re-importing same XLSX must not create duplicate instruments');
    } finally {
        @unlink($path);
    }
});

it('skips rows where the regulator code does not exist in the database', function (): void {
    importRefData();

    $rows = [
        [
            'source_title'    => 'Valid Instrument',
            'regulator_code'  => 'CBN',
            'instrument_type' => 'Act',
            'nature'          => 'Statutory',
            'status'          => 'Active',
            'area_of_focus'   => 'AML/CFT',
            'risk_rating'     => 'High',
            'applicability'   => 'Yes',
            'objectives'      => '',
            'date_issue'      => '',
            'date_commence'   => '',
            'link_url'        => '',
        ],
        [
            'source_title'    => 'Invalid Regulator Instrument',
            'regulator_code'  => 'NONEXISTENT',
            'instrument_type' => 'Act',
            'nature'          => 'Statutory',
            'status'          => 'Active',
            'area_of_focus'   => 'AML/CFT',
            'risk_rating'     => 'High',
            'applicability'   => 'Yes',
            'objectives'      => '',
            'date_issue'      => '',
            'date_commence'   => '',
            'link_url'        => '',
        ],
    ];

    $path = buildXlsxFixture($rows);

    try {
        Excel::import(new InstrumentImport, $path);

        $this->assertDatabaseHas('instruments', ['source_title' => 'Valid Instrument']);
        $this->assertDatabaseMissing('instruments', ['source_title' => 'Invalid Regulator Instrument']);
    } finally {
        @unlink($path);
    }
});

it('imported instruments emit audit events with action instrument.imported', function (): void {
    importRefData();

    $initialAuditCount = \Illuminate\Support\Facades\DB::table('audit_events')
        ->where('action', 'instrument.imported')
        ->count();

    $path = buildXlsxFixture(makeImportRows(3));

    try {
        Excel::import(new InstrumentImport, $path);

        $finalAuditCount = \Illuminate\Support\Facades\DB::table('audit_events')
            ->where('action', 'instrument.imported')
            ->count();

        expect($finalAuditCount - $initialAuditCount)->toBe(3,
            'Each imported instrument must produce exactly one instrument.imported audit event');
    } finally {
        @unlink($path);
    }
});

it('imports instruments with two different regulators correctly', function (): void {
    importRefData();

    $rows = [
        [
            'source_title'    => 'CBN Import Instrument',
            'regulator_code'  => 'CBN',
            'instrument_type' => 'Act',
            'nature'          => 'Statutory',
            'status'          => 'Active',
            'area_of_focus'   => 'AML/CFT',
            'risk_rating'     => 'High',
            'applicability'   => 'Yes',
            'objectives'      => '',
            'date_issue'      => '',
            'date_commence'   => '',
            'link_url'        => '',
        ],
        [
            'source_title'    => 'SEC Import Instrument',
            'regulator_code'  => 'SEC',
            'instrument_type' => 'Regulation',
            'nature'          => 'Statutory',
            'status'          => 'Active',
            'area_of_focus'   => 'AML/CFT',
            'risk_rating'     => 'Medium',
            'applicability'   => 'Yes',
            'objectives'      => '',
            'date_issue'      => '',
            'date_commence'   => '',
            'link_url'        => '',
        ],
    ];

    $path = buildXlsxFixture($rows);

    try {
        Excel::import(new InstrumentImport, $path);

        $cbn = Regulator::where('code', 'CBN')->first();
        $sec = Regulator::where('code', 'SEC')->first();

        $this->assertDatabaseHas('instruments', [
            'source_title' => 'CBN Import Instrument',
            'regulator_id' => $cbn->id,
        ]);
        $this->assertDatabaseHas('instruments', [
            'source_title' => 'SEC Import Instrument',
            'regulator_id' => $sec->id,
        ]);
    } finally {
        @unlink($path);
    }
});
