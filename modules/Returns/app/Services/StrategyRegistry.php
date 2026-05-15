<?php

declare(strict_types=1);

namespace Modules\Returns\Services;

use Modules\Returns\Contracts\DraftStrategy;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Strategies\ManualStubStrategy;

/**
 * Registry that maps "{regulator}:{submission_channel}" keys to DraftStrategy
 * implementations.
 *
 * Strategies are registered in ReturnsServiceProvider::register(). Unknown
 * keys fall back to ManualStubStrategy so the workflow is never blocked.
 */
class StrategyRegistry
{
    /** @var array<string, string> */
    private array $registry = [];

    /**
     * Register a strategy class for the given key.
     * Key format: "{regulator}:{submission_channel}" e.g. "nfiu:goaml_xml".
     *
     * @param  class-string<DraftStrategy>  $strategyClass
     */
    public function register(string $key, string $strategyClass): void
    {
        $this->registry[$key] = $strategyClass;
    }

    /**
     * Resolve the strategy for a given ReturnDefinition.
     *
     * Falls back to a "manual" channel key, then to the first registered
     * strategy that handles the same channel (portal / api / sftp / email).
     * If nothing matches, returns ManualStubStrategy to keep the workflow
     * unblocked rather than throwing at resolution time.
     */
    public function resolve(ReturnDefinition $def): DraftStrategy
    {
        $exactKey = "{$def->regulator}:{$def->submission_channel}";

        if (isset($this->registry[$exactKey])) {
            return app($this->registry[$exactKey]);
        }

        // Channel-only fallback (e.g. "*:manual")
        $channelKey = "*:{$def->submission_channel}";
        if (isset($this->registry[$channelKey])) {
            return app($this->registry[$channelKey]);
        }

        // Default to ManualStubStrategy when no match is found
        return app(ManualStubStrategy::class);
    }
}
