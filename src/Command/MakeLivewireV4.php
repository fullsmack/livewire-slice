<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Illuminate\Console\Command;
use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use FullSmack\LaravelSlice\SliceNotRegistered;

class MakeLivewireV4 extends MakeCommand
{
    use SliceDefinitions;

    protected $name = 'livewire:make';

    public function handle()
    {
        $sliceName = $this->option('slice');

        if (!$sliceName)
        {
            return parent::handle();
        }

        try {
            $this->loadFromRegistry($sliceName);
        }
        catch (SliceNotRegistered $e)
        {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->registerSliceWithFinder();

        // Prepend the slice namespace to the component name so Livewire's
        // Finder resolves paths within the slice directory structure.
        $name = $this->argument('name');

        if ($name && !str_contains($name, '::'))
        {
            $this->input->setArgument('name', $this->sliceName . '::' . $name);
        }

        return parent::handle();
    }

    protected function registerSliceWithFinder(): void
    {
        $classNamespace = $this->getLivewireNamespace();
        $classPath = $this->getLivewireClassPath();
        $viewPath = $this->getLivewireViewPath();

        $this->finder->addNamespace(
            $this->sliceName,
            viewPath: $viewPath,
            classNamespace: $classNamespace,
            classPath: $classPath,
            classViewPath: $viewPath,
        );
    }

    protected function getLivewireNestedPath(): string
    {
        return Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('/');
    }

    protected function getLivewireNestedNamespace(): string
    {
        return Str::of(config('livewire-slice.namespace', 'livewire'))
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('\\');
    }

    protected function getLivewireClassPath(): string
    {
        return $this->sliceSourcePath($this->getLivewireNestedPath());
    }

    protected function getLivewireNamespace(): string
    {
        return $this->sliceNamespace() . '\\' . $this->getLivewireNestedNamespace();
    }

    protected function getLivewireViewPath(): string
    {
        $viewFolder = config('livewire-slice.view-folder', 'livewire');
        return $this->slicePath('resources/views/' . $viewFolder);
    }

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['slice', null, InputOption::VALUE_REQUIRED, 'The slice to create the component in'],
        ]);
    }
}
