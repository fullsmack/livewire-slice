<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Livewire\Component;
use Illuminate\Support\Str;

use FullSmack\LaravelSlice\Path;
use FullSmack\LaravelSlice\Slice;

final class LivewireComponentLocator
{
    public function relativeClassNamespace(): string
    {
        return Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('\\');
    }

    public function relativeClassPath(): string
    {
        return Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('/');
    }

    public function viewFolder(): string
    {
        return (string) config('livewire-slice.view-folder', 'livewire');
    }

    public function classNamespaceForSlice(Slice $slice): string
    {
        return $this->classNamespaceFromSliceNamespace($slice->namespace());
    }

    public function classNamespaceFromSliceNamespace(string $sliceNamespace): string
    {
        return $sliceNamespace .'\\'. $this->relativeClassNamespace();
    }

    public function classPathForSlice(Slice $slice): string
    {
        return $this->classPathFromSliceSourcePath($slice->sourcePath());
    }

    public function classPathFromSliceSourcePath(string $sliceSourcePath): string
    {
        return Path::join($sliceSourcePath, $this->relativeClassPath());
    }

    public function viewPathForSlice(Slice $slice): string
    {
        return $this->viewPathFromSlicePath($slice->path());
    }

    public function viewPathFromSlicePath(string $slicePath): string
    {
        return Path::join($slicePath, 'resources/views/' . $this->viewFolder());
    }

    public function viewName(string $sliceName, string $componentName): string
    {
        return $sliceName . '::' . $this->viewFolder() . '.' . $this->normalizeComponentName($componentName);
    }

    public function testPathFromSlicePath(string $slicePath): string
    {
        return Path::join($slicePath, 'tests/Livewire');
    }

    public function testNamespaceFromSliceTestNamespace(string $sliceTestNamespace): string
    {
        return $sliceTestNamespace . '\\Livewire';
    }

    public function resolveComponentClass(Slice $slice, string $componentName): ?string
    {
        $class = $this->classNamespaceForSlice($slice) .'\\'. $this->classSuffix($componentName);

        if (! is_subclass_of($class, Component::class))
        {
            return null;
        }

        return $class;
    }

    public function normalizeComponentName(string $componentName): string
    {
        return Str::of($componentName)
            ->replace(['/', '\\'], '.')
            ->explode('.')
            ->filter(static fn (string $segment): bool => $segment !== '')
            ->map(static fn (string $segment): string => Str::kebab($segment))
            ->implode('.');
    }

    public function classSuffix(string $componentName): string
    {
        return Str::of($componentName)
            ->replace(['/', '\\'], '.')
            ->explode('.')
            ->filter(static fn (string $segment): bool => $segment !== '')
            ->map(static fn (string $segment): string => Str::studly($segment))
            ->implode('\\');
    }
}
