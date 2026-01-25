<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use FullSmack\LivewireSlice\ComponentParserUsingCustomLocation;

class MakeLivewire extends MakeCommand
{
    use SliceDefinitions;

    protected $signature = 'livewire:make {name} {--force} {--inline} {--test} {--pest} {--slice=} {--stub=}';

    public function handle()
    {
        $this->defineSliceUsingOption();

        if(!$this->createInSlice())
        {
            return parent::handle();
        }

        config(['livewire.class_namespace' => $this->getLivewireNamespace()]);
        config(['livewire.view_path' => $this->getLivewireViewPath()]);

        $this->handleWithCustomLocation();
    }

    private function createInSlice(): bool
    {
        return $this->option('slice') !== null;
    }

    public function handleWithCustomLocation()
    {
        $this->parser = new ComponentParserUsingCustomLocation(
            config('livewire.class_namespace'),
            config('livewire.view_path'),
            $this->argument('name'),
            $this->option('stub'),
            $this->sliceName
        );

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

        if($class || $view)
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
        if (!$this->createInSlice()) {
            return parent::isFirstTimeMakingAComponent();
        }

        return ! File::isDirectory($this->getLivewireClassPath());
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
        return $this->slicePath . '/src/' . $this->getLivewireNestedPath();
    }

    protected function getLivewireNamespace(): string
    {
        return $this->sliceNamespace . '\\' . $this->getLivewireNestedNamespace();
    }

    protected function getLivewireViewPath(): string
    {
        $viewFolder = config('livewire-slice.view-folder', 'livewire');
        return $this->slicePath . '/resources/views/' . $viewFolder;
    }
}
