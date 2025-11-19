<?php

namespace ByJG\FeatureFlag;

/**
 * Internal handler for method calls via reflection
 * Used by FeatureFlagDispatcher::addClass() to wrap methods decorated with FeatureFlagAttribute
 * This handler creates an instance of the class if the method is not static
 */
class StaticMethodHandler implements FeatureFlagHandlerInterface
{
    protected bool $isStatic;
    protected object|null $instance = null;

    public function __construct(
        protected string $className,
        protected string $methodName
    )
    {
        if (!class_exists($this->className)) {
            throw new \InvalidArgumentException("Class {$this->className} does not exist");
        }

        if (!method_exists($this->className, $this->methodName)) {
            throw new \InvalidArgumentException("Method {$this->methodName} does not exist in class {$this->className}");
        }

        $reflection = new \ReflectionMethod($this->className, $this->methodName);
        $this->isStatic = $reflection->isStatic();

        if (!$this->isStatic) {
            $this->instance = new $this->className();
        }
    }

    #[\Override]
    public function execute(mixed ...$args): mixed
    {
        if ($this->isStatic) {
            return ($this->className)::{$this->methodName}(...$args);
        }

        return $this->instance->{$this->methodName}(...$args);
    }
}
