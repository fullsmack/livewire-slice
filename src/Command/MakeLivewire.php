<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Illuminate\Console\Command;
use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use FullSmack\LaravelSlice\SliceNotRegistered;
use FullSmack\LivewireSlice\ComponentParserUsingCustomLocation;

class MakeLivewire extends MakeCommand
{
    use SliceDefinitions;

    protected $signature = 'livewire:make {name} {--force} {--inline} {--test} {--pest} {--slice=} {--stub=} {--component-path=} {--view-path=}';

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

        config(['livewire.class_namespace' => $this->getLivewireNamespace()]);
        config(['livewire.view_path' => $this->getLivewireViewPath()]);

        return $this->handleWithCustomLocation();
    }

    public function handleWithCustomLocation()
    {
        $this->parser = new ComponentParserUsingCustomLocation(
            classNamespace: $this->getLivewireNamespace(),
            classPath: $this->getLivewireClassPath(),
            viewPath: $this->getLivewireViewPath(),
            testPath: $this->getLivewireTestPath(),
            testNamespace: $this->getLivewireTestNamespace(),
            rawComponentName: $this->argument('name'),
            sliceName: $this->sliceName,
            stubSubDirectory: $this->option('stub'),
            viewFolder: $this->getViewFolder(),
        );

        return $this->parentCommandOutput();
    }

    private function parentCommandOutput()
    {
        if (!$this->isClassNameValid($name = $this->parser->className()))
        {
            $this->line("<options=bold,reverse;fg=red> WHOOPS! </> 😳 \n");
            $this->line("<fg=red;options=bold>Class is invalid:</> {$name}");

            return;
        }

        if ($this->isReservedClassName($name))
        {
            $this->line("<options=bold,reverse;fg=red> WHOOPS! </> 😳 \n");
            $this->line("<fg=red;options=bold>Class is reserved:</> {$name}");

            return;
        }

        $force = $this->option('force');
        $inline = $this->option('inline');
        $test = $this->option('test') || $this->option('pest');
        $testType = $this->option('pest') ? 'pest' : 'phpunit';

        $showWelcomeMessage = $this->isFirstTimeMakingAComponent();

        $class = $this->createClass($force, $inline);
        $view = $this->createView($force, $inline);

        if ($test)
        {
            $test = $this->createTest($force, $testType);
        }

        if ($class || $view)
        {
            $this->line("<options=bold,reverse;fg=green> COMPONENT CREATED </> 🤙\n");
            $class && $this->line("<options=bold;fg=green>CLASS:</> {$this->parser->relativeClassPath()}");

            if (! $inline)
            {
                $view && $this->line("<options=bold;fg=green>VIEW:</>  {$this->parser->relativeViewPath()}");
            }

            if ($test)
            {
                $test && $this->line("<options=bold;fg=green>TEST:</>  {$this->parser->relativeTestPath()}");
            }

            if ($showWelcomeMessage && ! app()->runningUnitTests())
            {
                $this->writeWelcomeMessage();
            }
        }
    }

    public function isFirstTimeMakingAComponent()
    {
        if (!$this->runInSlice())
        {
            return parent::isFirstTimeMakingAComponent();
        }

        return ! File::isDirectory($this->getLivewireClassPath());
    }

    protected function getComponentNamespaceOption(): string
    {
        return $this->option('component-path') ?? config('livewire-slice.namespace', 'livewire');
    }

    protected function getViewFolder(): string
    {
        return $this->option('view-path') ?? config('livewire-slice.view-folder', 'livewire');
    }

    protected function getLivewireNestedPath(): string
    {
        return Str::of($this->getComponentNamespaceOption())
            ->explode('.')
            ->map(Str::studly(...))
            ->implode('/');
    }

    protected function getLivewireNestedNamespace(): string
    {
        return Str::of($this->getComponentNamespaceOption())
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
        $viewPath = str_replace('.', '/', $this->getViewFolder());

        return $this->slicePath('resources/views/' . $viewPath);
    }

    protected function getLivewireTestPath(): string
    {
        return $this->slicePath('tests/Livewire');
    }

    protected function getLivewireTestNamespace(): string
    {
        return $this->sliceTestNamespace('Livewire');
    }
}
