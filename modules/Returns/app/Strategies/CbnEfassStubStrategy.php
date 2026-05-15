<?php

declare(strict_types=1);

namespace Modules\Returns\Strategies;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Returns\Contracts\DraftStrategy;
use Modules\Returns\Models\ReturnRun;

/**
 * [MVP STUB] CBN eFASS submission strategy.
 *
 * THIS IS A PLACEHOLDER — it does NOT connect to the CBN eFASS SFTP server
 * or generate a valid eFASS-formatted file. It writes a minimal CSV stub to
 * storage and records a fake reference.
 *
 * Returns Phase 2 TODO:
 *   - Implement eFASS CSV/XLSX template per CBN reporting guidelines.
 *   - Upload via CBN eFASS SFTP (host, credentials from config/secrets).
 *   - Parse the eFASS portal confirmation email for acknowledgement.
 *   - Handle submission error codes from eFASS validation engine.
 */
class CbnEfassStubStrategy implements DraftStrategy
{
    public function regulatorCode(): string
    {
        return 'cbn';
    }

    public function submissionChannel(): string
    {
        return 'cbn_efass';
    }

    public function buildPayload(ReturnRun $run): ?string
    {
        $path = "returns/{$run->id}/efass_stub.csv";

        $csv = implode(',', ['period_label', 'period_start', 'period_end', 'generated_at'])."\n";
        $csv .= implode(',', [
            $run->period_label,
            $run->period_start->toDateString(),
            $run->period_end->toDateString(),
            now()->toIso8601String(),
        ])."\n";
        $csv .= "# MVP STUB: Replace with real eFASS payload in Returns Phase 2\n";

        Storage::put($path, $csv);

        return $path;
    }

    /**
     * [MVP STUB] Returns a fake CBN eFASS reference. No SFTP call is made.
     *
     * @return array{reference: string, submitted_at: Carbon, acknowledged: bool}
     */
    public function submit(ReturnRun $run): array
    {
        $random = strtoupper(substr(md5(uniqid('', true)), 0, 6));
        $reference = 'EFASS-'.now()->format('Ymd').'-'.$random;

        return [
            'reference' => $reference,
            'submitted_at' => now(),
            'acknowledged' => false, // eFASS sends email acknowledgement out-of-band
        ];
    }
}
