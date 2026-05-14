<?php

declare(strict_types=1);

namespace Modules\Library\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Library\Imports\InstrumentImport;

class BulkImportInstrumentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        private readonly string $filePath,
    ) {}

    public function handle(): void
    {
        Excel::import(new InstrumentImport, $this->filePath);
    }
}
