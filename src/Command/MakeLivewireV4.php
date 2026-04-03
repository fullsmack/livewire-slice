<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Illuminate\Console\Command;
use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;
use Symfony\Component\Console\Input\InputOption;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use FullSmack\LaravelSlice\SliceNotRegistered;
use FullSmack\LivewireSlice\LivewireComponentLocator;

class MakeLivewireV4 extends MakeCommand
{
    use SliceDefinitions;

    private ?LivewireComponentLocator $locator = null;

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

    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['slice', null, InputOption::VALUE_REQUIRED, 'The slice to create the component in'],
        ]);
    }

    protected function registerSliceWithFinder(): void
    {
        $classNamespace = $this->getLivewireNamespace();
        $classPath = $this->getLivewireClassPath();
        $viewPath = $this->getLivewireViewPath();

        app('livewire.finder')->addNamespace(
            $this->sliceName,
            viewPath: $viewPath,
            classNamespace: $classNamespace,
            classPath: $classPath,
            classViewPath: $viewPath,
        );
    }

    protected function getLivewireNestedPath(): string
    {
        return $this->locator()->relativeClassPath();
    }

    protected function getLivewireNestedNamespace(): string
    {
        return $this->locator()->relativeClassNamespace();
    }

    protected function getLivewireClassPath(): string
    {
        return $this->locator()->classPathFromSliceSourcePath($this->sliceSourcePath());
    }

    protected function getLivewireNamespace(): string
    {
        return $this->locator()->classNamespaceFromSliceNamespace($this->sliceNamespace());
    }

    protected function getLivewireViewPath(): string
    {
        return $this->locator()->viewPathFromSlicePath($this->slicePath());
    }

    private function locator(): LivewireComponentLocator
    {
        return $this->locator ??= app(LivewireComponentLocator::class);
    }
}
