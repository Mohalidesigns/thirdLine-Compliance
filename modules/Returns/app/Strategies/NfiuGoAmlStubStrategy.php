<?php

declare(strict_types=1);

namespace Modules\Returns\Strategies;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Returns\Contracts\DraftStrategy;
use Modules\Returns\Models\ReturnRun;

/**
 * [MVP STUB] NFIU goAML XML submission strategy.
 *
 * THIS IS A PLACEHOLDER — it does NOT submit to goAML or generate a
 * schema-valid NFIU XML payload. It writes a minimal XML stub to storage
 * and records a fake reference.
 *
 * Returns Phase 2 TODO:
 *   - Implement the real goAML XSLT/XML schema (FATF FIU standard).
 *   - Call the NFIU goAML REST API with proper credentials.
 *   - Parse the acknowledgement response and record it.
 *   - Handle the goAML case number as the submission_reference.
 */
class NfiuGoAmlStubStrategy implements DraftStrategy
{
    public function regulatorCode(): string
    {
        return 'nfiu';
    }

    public function submissionChannel(): string
    {
        return 'goaml_xml';
    }

    public function buildPayload(ReturnRun $run): ?string
    {
        $path = "returns/{$run->id}/goaml.xml";

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!-- MVP STUB: Replace with real goAML XML payload in Returns Phase 2 -->
<goAML>
  <report>
    <submission_reference>PENDING</submission_reference>
    <period_label>{$run->period_label}</period_label>
    <period_start>{$run->period_start->toDateString()}</period_start>
    <period_end>{$run->period_end->toDateString()}</period_end>
    <generated_at>{$run->updated_at?->toIso8601String()}</generated_at>
  </report>
</goAML>
XML;

        Storage::put($path, $xml);

        return $path;
    }

    /**
     * [MVP STUB] Returns a fake NFIU reference. No network call is made.
     *
     * @return array{reference: string, submitted_at: Carbon, acknowledged: bool}
     */
    public function submit(ReturnRun $run): array
    {
        $random = strtoupper(substr(md5(uniqid('', true)), 0, 6));
        $reference = 'NFIU-'.now()->format('Ymd').'-'.$random;

        return [
            'reference' => $reference,
            'submitted_at' => now(),
            'acknowledged' => false, // NFIU acknowledges asynchronously
        ];
    }
}
