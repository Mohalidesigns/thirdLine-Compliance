<?php

declare(strict_types=1);

namespace Modules\Policy\Jobs;

use App\Services\AuditWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Policy\Models\Policy;

class RenderPolicyPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly int $policyId,
    ) {}

    public function handle(AuditWriter $auditWriter): void
    {
        $policy = Policy::findOrFail($this->policyId);

        $pdf = Pdf::loadView('policy.pdf', ['policy' => $policy])
            ->setPaper('a4', 'portrait');

        $relativePath = "policies/{$policy->id}/v{$policy->version}.pdf";
        $absolutePath = storage_path('app/'.$relativePath);

        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absolutePath, $pdf->output());

        $policy->published_pdf_path = $relativePath;
        $policy->saveQuietly();

        $auditWriter->record('policy.pdf_rendered', $policy, [
            'version' => $policy->version,
            'path' => $relativePath,
        ]);
    }
}
