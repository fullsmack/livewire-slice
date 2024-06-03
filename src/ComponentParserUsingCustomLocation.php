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

        $sliceRootFolder = Str::of($parts[0])
            ->replace(Str::studly($config['namespace']), $config['folder']);

        $sliceName = Str::kebab($parts[1]);

        $livewireFolder = Collection::make($parts)->skip(2)->implode('/');

        $path = base_path("{$sliceRootFolder}/{$sliceName}/src/{$livewireFolder}");

        return $path;
    }

    public static function generateTestPathFromNamespace($namespace)
    {
        $rootFolder = config('laravel-slice.root.folder');

        $testNamespace = config('laravel-slice.test.namespace');

        $parts = Str::of($namespace)->explode('\\');

        $sliceRootFolder = Str::of($parts[0])
            ->replace(Str::studly($testNamespace), $rootFolder);

        $sliceName = Str::kebab($parts[1]);

        return base_path("{$sliceRootFolder}/{$sliceName}/tests");
    }

    public function viewName()
    {
        $sliceRootFolder = config('laravel-slice.root.folder');

        $sliceName = Str::of($this->baseViewPath)
            ->before('/resources/views')
            ->replace("{$sliceRootFolder}/", '');

        return $sliceName .'::'. Collection::make()
            ->when(
                config('livewire.view_path') !== resource_path(),
                function ($collection) {
                    return $collection->concat(
                        Str::of($this->baseViewPath)
                            ->after('/resources/views')->explode('/')
                    );
                }
            )
            ->filter()
            ->concat($this->directories)
            ->map([Str::class, 'kebab'])
            ->push($this->component)
            ->implode('.');
    }
}