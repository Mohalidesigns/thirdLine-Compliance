<?php

declare(strict_types=1);

namespace Modules\Controls\Contracts;

interface CcmRule
{
    public function name(): string;

    public function cadence(): string;

    public function threshold(): float;

    /**
     * Evaluate the rule and return the aggregate result plus per-item breaches.
     *
     * Return shape:
     * ```
     * [
     *   'metric_value' => float,
     *   'breached'     => bool,
     *   'breaches'     => [
     *     [
     *       'severity' => 'low'|'medium'|'high'|'critical',
     *       'subject'  => ['type' => FQCN, 'id' => int],
     *       'detail'   => string,   // human-readable description
     *     ],
     *     ...
     *   ],
     * ]
     * ```
     *
     * When `breached` is false the `breaches` array is empty.
     * Implementations that previously returned only `metric_value` + `breached`
     * must now also return `breaches`.
     *
     * @return array{
     *   metric_value: float,
     *   breached: bool,
     *   breaches: list<array{severity: string, subject: array{type: string, id: int}, detail: string}>
     * }
     */
    public function evaluate(): array;

    public function kciName(): string;
}
