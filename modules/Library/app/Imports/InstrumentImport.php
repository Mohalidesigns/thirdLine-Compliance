<?php

declare(strict_types=1);

namespace Modules\Library\Imports;

use App\Services\AuditWriter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;

class InstrumentImport implements ToCollection, WithHeadingRow
{
    private AuditWriter $auditWriter;

    public function __construct()
    {
        $this->auditWriter = app(AuditWriter::class);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $sourceRowHash = md5(json_encode($row->toArray(), JSON_THROW_ON_ERROR));

            $existing = Instrument::withoutGlobalScopes()
                ->where('source_title', $row['source_title'] ?? '')
                ->first();

            if ($existing !== null) {
                continue;
            }

            $regulator = Regulator::where('code', $row['regulator_code'] ?? '')->first();
            $type = InstrumentType::where('name', $row['instrument_type'] ?? '')->first();
            $nature = Nature::where('name', $row['nature'] ?? '')->first();
            $status = Status::where('name', $row['status'] ?? 'Active')->first();
            $area = AreaOfFocus::where('name', $row['area_of_focus'] ?? '')->first();
            $risk = RiskRating::where('name', $row['risk_rating'] ?? 'Low')->first();

            if ($regulator === null || $type === null || $nature === null || $status === null || $area === null || $risk === null) {
                continue;
            }

            $instrument = Instrument::create([
                'source_title' => $row['source_title'],
                'objectives' => $row['objectives'] ?? null,
                'date_issue' => $row['date_issue'] ?? null,
                'date_commence' => $row['date_commence'] ?? null,
                'regulator_id' => $regulator->id,
                'instrument_type_id' => $type->id,
                'nature_id' => $nature->id,
                'status_id' => $status->id,
                'area_of_focus_id' => $area->id,
                'risk_rating_id' => $risk->id,
                'applicability' => $row['applicability'] ?? 'Yes',
                'link_url' => $row['link_url'] ?? null,
            ]);

            $this->auditWriter->record('instrument.imported', $instrument, [
                'source_row_hash' => $sourceRowHash,
                'imported_by' => auth()->id(),
            ]);
        }
    }
}
