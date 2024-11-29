<?php

namespace ByJG\FeatureFlag;

enum SearchOrder
{
    case Selector;

    case FeatureFlags;

    case Custom;
}
