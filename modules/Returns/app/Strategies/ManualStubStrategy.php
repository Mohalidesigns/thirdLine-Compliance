<?php

declare(strict_types=1);

namespace Modules\Returns\Strategies;

use Illuminate\Support\Carbon;
use Modules\Returns\Contracts\DraftStrategy;
use Modules\Returns\Models\ReturnRun;

/**
 * Manual submission strategy.
 *
 * Used when submission_channel = 'manual'. The maker uploads evidence of
 * submission manually (a PDF, screenshot, email, etc.) via the
 * acknowledgement upload flow. No payload file is generated here;
 * the submission reference is supplied by the maker at sign-off time.
 *
 * submit() is a no-op that simply records the current timestamp and an
 * empty reference — the caller (ReturnsService::signOffAndSubmit) is
 * responsible for supplying the manual reference.
 */
class ManualStubStrategy implements DraftStrategy
{
    public function regulatorCode(): string
    {
        return 'other'; // generic fallback; registry key is 'manual' channel
    }

    public function submissionChannel(): string
    {
        return 'manual';
    }

    /**
     * No payload file is generated for manual submissions.
     */
    public function buildPayload(ReturnRun $run): ?string
    {
        return null;
    }

    /**
     * No-op: manual submissions are recorded by the maker uploading
     * acknowledgement documents rather than via an API call.
     *
     * @return array{reference: string, submitted_at: Carbon, acknowledged: bool}
     */
    public function submit(ReturnRun $run): array
    {
        return [
            'reference' => $run->submission_reference ?? '',
            'submitted_at' => now(),
            'acknowledged' => false,
        ];
    }
}
