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
    private array $componentPaths = [];

    private array $viewPaths = [];

    private ?string $componentPathDefault = null;

    private ?string $viewPathDefault = null;

    public static function configure(): self
    {
        return new self();
    }

    public function componentPath(string $namespace): self
    {
        $this->componentPaths[] = $namespace;

        return $this;
    }

    public function viewPath(string $namespace): self
    {
        $this->viewPaths[] = $namespace;

        return $this;
    }

    public function path(string $namespace): self
    {
        $this->componentPaths[] = $namespace;
        $this->viewPaths[] = $namespace;

        return $this;
    }

    public function componentPathDefault(string $namespace): self
    {
        $this->componentPathDefault = $namespace;

        return $this;
    }

    public function viewPathDefault(string $namespace): self
    {
        $this->viewPathDefault = $namespace;

        return $this;
    }

    public function pathDefault(string $namespace): self
    {
        $this->componentPathDefault = $namespace;
        $this->viewPathDefault = $namespace;

        return $this;
    }

    public function getComponentNamespace(): string
    {
        return $this->componentPathDefault
            ?? $this->componentPaths[0]
            ?? config('livewire-slice.namespace', 'livewire');
    }

    public function getViewNamespace(): string
    {
        return $this->viewPathDefault
            ?? $this->viewPaths[0]
            ?? config('livewire-slice.view-folder', 'livewire');
    }

    public function getComponentPaths(): array
    {
        $paths = $this->componentPaths;

        if ($this->componentPathDefault !== null && !in_array($this->componentPathDefault, $paths, true))
        {
            array_unshift($paths, $this->componentPathDefault);
        }

        return !empty($paths) ? $paths : [config('livewire-slice.namespace', 'livewire')];
    }

    public function getViewPaths(): array
    {
        $paths = $this->viewPaths;

        if ($this->viewPathDefault !== null && !in_array($this->viewPathDefault, $paths, true))
        {
            array_unshift($paths, $this->viewPathDefault);
        }

        return !empty($paths)
            ? $paths
            : [config('livewire-slice.view-folder', 'livewire')];
    }

    public function register(Slice $slice): void
    {
        foreach ($this->getComponentPaths() as $componentNamespace)
        {
            $this->registerComponentsFrom($slice, $componentNamespace);
        }
    }

    private function registerComponentsFrom(Slice $slice, string $componentNamespace): void
    {
        $sliceSourcePath = $slice->sourcePath();

        $sliceName = $slice->name();

        $livewireNamespace = Str::of($componentNamespace)
            ->explode('.')
            ->map(static fn($string): string => ucfirst($string))
            ->implode('\\');

        $namespace = $slice->namespace($livewireNamespace);

        $livewirePath = Str::of($componentNamespace)
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
