<?php

namespace ByJG\FeatureFlag\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class FeatureFlagAttribute
{
    public function __construct(
        protected string $featureFlag,
        protected ?string $featureValue = null
    )
    {
    }

    public function getFeatureFlag(): string
    {
        return $this->featureFlag;
    }

    public function getFeatureValue(): ?string
    {
        return $this->featureValue;
    }
}