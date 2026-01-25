<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Livewire\Features\SupportConsoleCommands\Commands\ComponentParser;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ComponentParserUsingCustomLocation extends ComponentParser
{
    public static function generatePathFromNamespace($namespace)
    {
        $config = config('laravel-slice.root');

        $parts = Str::of($namespace)->explode('\\');

        $sliceRootFolder = $config['folder'];

        $livewireConfig = config('livewire-slice.namespace', 'livewire'); // Default to 'livewire' if not set

        $livewireNamespaceParts = Str::of($livewireConfig)
            ->explode('.')
            ->map(Str::studly(...));

        // Find where the livewire namespace starts in the full namespace
        $livewireStartIndex = null;
        for ($i = 0; $i < $parts->count(); $i++) {
            $matches = true;
            foreach ($livewireNamespaceParts as $j => $part) {
                if (!isset($parts[$i + $j]) || $parts[$i + $j] !== $part) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $livewireStartIndex = $i;
                break;
            }
        }

        if ($livewireStartIndex === null) {
            throw new \RuntimeException("Could not find livewire namespace parts in: {$namespace}");
        }

        // Extract the slice path parts (everything between root namespace and livewire namespace)
        $sliceParts = Collection::make($parts)
            ->skip(1) // Skip root namespace (Slice)
            ->take($livewireStartIndex - 1) // Take until we hit livewire namespace
            ->map(Str::kebab(...));

        $slicePath = $sliceParts->implode('/');

        $livewireFolderPath = $livewireNamespaceParts->implode('/');

        return base_path("{$sliceRootFolder}/{$slicePath}/src/{$livewireFolderPath}");
    }

    public static function generateTestPathFromNamespace($namespace)
    {
        $rootFolder = config('laravel-slice.root.folder');

        $parts = Str::of($namespace)->explode('\\');

        // Get livewire namespace from config and convert to namespace parts
        $livewireConfig = config('livewire-slice.namespace', 'livewire'); // Default to 'livewire' if not set
        $livewireNamespaceParts = Str::of($livewireConfig)
            ->explode('.')
            ->map(Str::studly(...));

        // Find where the livewire namespace starts
        $livewireStartIndex = null;
        for ($i = 0; $i < $parts->count(); $i++) {
            $matches = true;
            foreach ($livewireNamespaceParts as $j => $part) {
                if (!isset($parts[$i + $j]) || $parts[$i + $j] !== $part) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $livewireStartIndex = $i;
                break;
            }
        }

        if ($livewireStartIndex === null) {
            throw new \RuntimeException("Could not find livewire namespace parts in: {$namespace}");
        }

        // Extract the slice path parts (everything between root namespace and livewire namespace)
        $sliceParts = Collection::make($parts)
            ->skip(1) // Skip root namespace
            ->take($livewireStartIndex - 1) // Take until we hit livewire namespace
            ->map(Str::kebab(...)); // Convert to kebab case

        $slicePath = $sliceParts->implode('/');

        return base_path("{$rootFolder}/{$slicePath}/tests");
    }

    public function viewName()
    {
        $sliceRootFolder = config('laravel-slice.root.folder');
        $viewFolder = config('livewire-slice.view-folder');

        // Extract slice name from the base view path
        // Find the slice root folder in the path and extract everything between it and /resources/views
        $pathParts = Str::of($this->baseViewPath)->replace('\\', '/');

        // Find the position of the slice root folder
        $sliceRootPos = $pathParts->position("/{$sliceRootFolder}/");

        if ($sliceRootPos === false) {
            // Fallback: just use the path as-is
            $sliceName = $pathParts->before('/resources/views')->afterLast('/');
        } else {
            // Extract everything between /{sliceRootFolder}/ and /resources/views
            $sliceName = $pathParts
                ->substr($sliceRootPos + strlen("/{$sliceRootFolder}/"))
                ->before('/resources/views');
        }

        // Build the component path WITH the livewire folder prefix (for view() calls)
        // This creates: 'blog::livewire.create-blog'
        return $sliceName . '::' . $viewFolder . '.' . Collection::make($this->directories)
            ->map(Str::kebab(...))
            ->push($this->component)
            ->implode('.');
    }
}
