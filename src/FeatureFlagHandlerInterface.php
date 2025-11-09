<?php

namespace ByJG\FeatureFlag;

interface FeatureFlagHandlerInterface
{
    /**
     * Execute the feature flag handler
     *
     * @param mixed ...$args Arguments to pass to the handler
     * @return mixed The result of the handler execution
     */
    public function execute(mixed ...$args): mixed;
}
