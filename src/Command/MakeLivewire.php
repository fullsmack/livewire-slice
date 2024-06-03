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

        $config = config('livewire-slice');

        $slicePascalName = Str::studly($this->sliceName);

        $namespace = Str::of($config['namespace'])
            ->explode('.')
            ->map(fn(string $string) => Str::studly($string))
            ->implode('\\');

        $viewFolder = Str::of($config['view-folder'])
            ->explode('.')
            ->implode('/');

        config(['livewire.class_namespace' => "{$this->sliceRootNamespace}\\{$slicePascalName}\\{$namespace}"]);
        config(['livewire.view_path' => "{$this->sliceRootFolder}/{$this->sliceName}/resources/views/{$viewFolder}"]);

        $this->handleWithCustomLocation();
    }

    public function handleWithCustomLocation()
    {
        $this->parser = new ComponentParserUsingCustomLocation(
            config('livewire.class_namespace'),
            config('livewire.view_path'),
            $this->argument('name'),
            $this->option('stub')
        );

        if (!$this->isClassNameValid($name = $this->parser->className())) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS! </> 😳 \n");
            $this->line("<fg=red;options=bold>Class is invalid:</> {$name}");

            return;
        }

        if ($this->isReservedClassName($name)) {
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

        if ($test) {
            $test = $this->createTest($force, $testType);
        }

        if($class || $view) {
            $this->line("<options=bold,reverse;fg=green> COMPONENT CREATED </> 🤙\n");
            $class && $this->line("<options=bold;fg=green>CLASS:</> {$this->parser->relativeClassPath()}");

            if (! $inline) {
                $view && $this->line("<options=bold;fg=green>VIEW:</>  {$this->parser->relativeViewPath()}");
            }

            if ($test) {
                $test && $this->line("<options=bold;fg=green>TEST:</>  {$this->parser->relativeTestPath()}");
            }

            if ($showWelcomeMessage && ! app()->runningUnitTests()) {
                $this->writeWelcomeMessage();
            }
        }
    }

    public function isFirstTimeMakingAComponent()
    {
        $livewireFolder = config('livewire-slice.namespace');

        $livewireFolder = Str::of($livewireFolder)->replace('.', '/');

        $path = base_path("{$this->sliceRootFolder}/{$this->sliceName}/src/{$livewireFolder}");

        return ! File::isDirectory($path);
    }
}
