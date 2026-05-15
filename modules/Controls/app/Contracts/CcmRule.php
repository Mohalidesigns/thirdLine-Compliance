<?php

declare(strict_types=1);

namespace Modules\Controls\Contracts;

interface CcmRule
{
    public function name(): string;

    public function cadence(): string;

    public function threshold(): float;

    /**
     * @return array{metric_value: float, breached: bool}
     */
    public function evaluate(): array;

    public function kciName(): string;
}
