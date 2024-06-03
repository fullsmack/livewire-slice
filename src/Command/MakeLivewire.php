<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use Illuminate\Support\Str;

class MakeLivewire extends MakeCommand
{
    use SliceDefinitions;

    protected $signature = 'livewire:make {name} {--force} {--inline} {--test} {--pest} {--slice=} {--stub=}';

    public function handle()
    {
        $this->defineSliceUsingOption();

        $config = config('livewire-slice');

        $slicePascalName = Str::studly($this->sliceName);

        $namespace = Str::of($config['namespace'])
            ->explode('.')
            ->map(fn(string $string) => Str::studly($string))
            ->implode('\\');

        $viewFolder = Str::of($config['view-folder'])
            ->explode('.')
            ->implode('/');

        config(['livewire.class_namespace' => "{$this->sliceRootNamespace}\\{$slicePascalName}\\{$namespace}\\"]);
        config(['livewire.view_path' => "{$this->sliceRootFolder}/{$this->sliceName}/resources/views/{$viewFolder}"]);

        parent::handle();
    }
}
