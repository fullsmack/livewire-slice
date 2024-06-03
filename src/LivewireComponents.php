<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use ReflectionClass;
use Livewire\Component;
use Livewire\Livewire;

use FullSmack\LaravelSlice\Slice;
use FullSmack\LaravelSlice\Feature;

class LivewireComponents implements Feature
{
    private string $componentNamespace = 'livewire';

    public function register(Slice $slice): void
    {
        $moduleSourcePath = $slice->basePath();
        $moduleName = $slice->name();
        $livewireNamespace = Str::of($this->componentNamespace)
            ->explode('.')
            ->map(fn($string) => ucfirst($string))
            ->implode('\\');

        $namespace = $slice->baseNamespace($livewireNamespace);

        $livewireNestedSourcePath ??= (fn(): Stringable => Str::of($livewireNamespace)
            // ->lower()
            ->after($moduleName.'\\')
            ->replace('\\', '/'))();

        $directory = $moduleSourcePath .DIRECTORY_SEPARATOR. $livewireNestedSourcePath;

        $filesystem = app(Filesystem::class);

        Collection::make($filesystem->allFiles($directory))
            ->map(function (SplFileInfo $file) use ($namespace): string {
                return (string) Str::of($namespace)
                    ->append('\\', $file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => (
                is_subclass_of($class, Component::class) &&
                (! (new ReflectionClass($class))->isAbstract()))
            )
            ->each(function (string $class) use ($namespace, $moduleName): void {
                $alias = Str::of($class)
                    ->after($namespace . '\\')
                    ->replace(['/', '\\'], '.')
                    ->explode('.')
                    ->map(Str::kebab(...))
                    ->implode('.');

                Livewire::component($moduleName.'::'.$alias, $class);
            });
    }
}
