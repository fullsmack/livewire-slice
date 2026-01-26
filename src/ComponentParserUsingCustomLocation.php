<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Livewire\Features\SupportConsoleCommands\Commands\ComponentParser;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ComponentParserUsingCustomLocation extends ComponentParser
{
    protected string $sliceName;

    public function __construct($classNamespace, $baseViewPath, $rawComponentName, $stub = null, string $sliceName = null)
    {
        parent::__construct($classNamespace, $baseViewPath, $rawComponentName, $stub);

        $this->sliceName = $sliceName ?? $this->extractSliceNameFromPath($baseViewPath);
    }

    protected function extractSliceNameFromPath(string $baseViewPath): string
    {
        $sliceRootFolder = config('laravel-slice.root.folder');
        $pathParts = Str::of($baseViewPath)->replace('\\', '/');
        $sliceRootPos = $pathParts->position("/{$sliceRootFolder}/");

        if ($sliceRootPos === false)
        {
            return (string) $pathParts->before('/resources/views')->afterLast('/');
        }

        return (string) $pathParts
            ->substr($sliceRootPos + strlen("/{$sliceRootFolder}/"))
            ->before('/resources/views')
            ->replace('/', '.');
    }

    public static function generatePathFromNamespace($namespace)
    {
        $livewireNestedPath = Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('/');

        $sliceRootFolder = config('laravel-slice.root.folder');

        $namespaceParts = Str::of($namespace)->explode('\\');

        $livewireNamespaceParts = Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...));

        $sliceParts = $namespaceParts
            ->skip(1)
            ->takeUntil(fn($part) => $livewireNamespaceParts->contains($part))
            ->map(Str::kebab(...));

        $slicePath = $sliceParts->implode('/');

        return base_path("{$sliceRootFolder}/{$slicePath}/src/{$livewireNestedPath}");
    }

    public static function generateTestPathFromNamespace($namespace)
    {
        $sliceRootFolder = config('laravel-slice.root.folder');

        $namespaceParts = Str::of($namespace)->explode('\\');

        $livewireNamespaceParts = Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...));

        $sliceParts = $namespaceParts
            ->skip(1)
            ->takeUntil(fn($part) => $livewireNamespaceParts->contains($part))
            ->map(Str::kebab(...));

        $slicePath = $sliceParts->implode('/');

        return base_path("{$sliceRootFolder}/{$slicePath}/tests");
    }

    public function viewName()
    {
        $viewFolder = config('livewire-slice.view-folder');

        return $this->sliceName . '::' . $viewFolder . '.' . Collection::make($this->directories)
            ->map(Str::kebab(...))
            ->push($this->component)
            ->implode('.');
    }
}
