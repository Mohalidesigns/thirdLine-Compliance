<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use Illuminate\Support\Carbon;
use Modules\Returns\Models\ReturnRun;

/**
 * Strategy contract for building and submitting regulatory returns.
 *
 * Each regulator/channel combination has its own implementation. The two
 * stub implementations (NfiuGoAmlStubStrategy, CbnEfassStubStrategy) do
 * NOT perform real submissions — they are MVP placeholders that generate
 * minimal placeholder files and record a fake reference. See each class
 * for the [MVP STUB] notice.
 *
 * Replace the stub implementations in "Returns Phase 2" when real
 * regulator API/SFTP integrations are available.
 */
interface DraftStrategy
{
    /**
     * The regulator code this strategy handles (matches return_definitions.regulator).
     */
    public function regulatorCode(): string;

    /**
     * The submission channel this strategy handles (matches return_definitions.submission_channel).
     */
    public function submissionChannel(): string;

    /**
     * Build the payload file for this return run. Must persist the file to
     * storage and return its path (e.g. "returns/42/goaml.xml").
     *
     * Returns null when no payload is needed (e.g. ManualStubStrategy).
     */
    public function buildPayload(ReturnRun $run): ?string;

    /**
     * Submit the payload to the regulator.
     *
     * Returns an array with:
     *   - reference (string): the regulator's submission reference
     *   - submitted_at (Carbon): the timestamp of submission
     *   - acknowledged (bool): whether an instant acknowledgement was received
     *
     * @return array{reference: string, submitted_at: Carbon, acknowledged: bool}
     */
    public function submit(ReturnRun $run): array;
}
