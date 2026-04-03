<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use FullSmack\LaravelSlice\Slice;
use FullSmack\LivewireSlice\LivewireComponentLocator;

final class LivewireSliceRegistry
{
    /** @var array<string, Slice> */
    private array $slices = [];

    public function __construct(
        private readonly LivewireComponentLocator $locator,
    ) {}

    public function register(Slice $slice): void
    {
        $this->slices[$slice->name()] = $slice;
    }

    public function has(string $sliceName): bool
    {
        return isset($this->slices[$sliceName]);
    }

    public function resolve(string $name): ?string
    {
        if (! str_contains($name, '::'))
        {
            return null;
        }

        [$sliceName, $componentName] = explode('::', $name, 2);

        if ($componentName === '' || ! isset($this->slices[$sliceName]))
        {
            return null;
        }

        return $this->locator->resolveComponentClass($this->slices[$sliceName], $componentName);
    }

    public function __invoke(string $name): ?string
    {
        return $this->resolve($name);
    }

    public function clear(): void
    {
        $this->slices = [];
    }
}
