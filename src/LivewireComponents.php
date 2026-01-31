<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use ReflectionClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Livewire\Livewire;
use Livewire\Component;

use FullSmack\LaravelSlice\Slice;
use FullSmack\LaravelSlice\Extension;

class LivewireComponents implements Extension
{
    private string $componentNamespace = 'livewire';

    private ?string $viewNamespace = null;

    public static function configure(): self
    {
        return new self();
    }

    public function componentPath(string $namespace): self
    {
        $this->componentNamespace = $namespace;

        return $this;
    }

    public function viewPath(string $namespace): self
    {
        $this->viewNamespace = $namespace;

        return $this;
    }

    public function path(string $namespace): self
    {
        $this->componentNamespace = $namespace;
        $this->viewNamespace = $namespace;

        return $this;
    }

    public function getComponentNamespace(): string
    {
        return $this->componentNamespace;
    }

    public function getViewNamespace(): string
    {
        return $this->viewNamespace ?? config('livewire-slice.view-folder', 'livewire');
    }

    public function register(Slice $slice): void
    {
        $sliceSourcePath = $slice->sourcePath();

        $sliceName = $slice->name();

        $livewireNamespace = Str::of($this->componentNamespace)
            ->explode('.')
            ->map(static fn($string): string => ucfirst($string))
            ->implode('\\');

        $namespace = $slice->namespace($livewireNamespace);

        $livewirePath = Str::of($this->componentNamespace)
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('/');

        $directory = $sliceSourcePath .DIRECTORY_SEPARATOR. $livewirePath;

        $filesystem = app(Filesystem::class);

        if(!$filesystem->exists($directory))
        {
            return;
        }

        Collection::make($filesystem->allFiles($directory))
            ->map(function (SplFileInfo $file) use ($namespace): string {
                return (string) Str::of($namespace)
                    ->append('\\', $file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => (
                is_subclass_of($class, Component::class)
                && (! (new ReflectionClass($class))->isAbstract())
            ))
            ->each(function (string $class) use ($namespace, $sliceName): void {
                $alias = Str::of($class)
                    ->after($namespace . '\\')
                    ->replace(['/', '\\'], '.')
                    ->explode('.')
                    ->map(Str::kebab(...))
                    ->implode('.');

                Livewire::component($sliceName.'::'.$alias, $class);
            });
    }
}
