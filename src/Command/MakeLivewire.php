<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;

use FullSmack\LaravelSlice\Command\SliceDefinitions;
use Illuminate\Support\Str;

class MakeLivewire extends MakeCommand
{
    use SliceDefinitions;

    protected $signature = 'make:livewire {name} {--force} {--inline} {--test} {--pest} {--slice=} {--stub=}';

    public function handle()
    {
        $this->defineSliceUsingOption();

        $config = config('livewire-slice');

        $namespace = Str::of($config['namespace'])
            ->explode('.')
            ->map(fn(string $string) => ucfirst($string))
            ->implode('\\');

        $viewFolder = Str::of($config['view-folder'])
            ->explode('.')
            ->implode('/');

        config(['livewire.class_namespace' => "{$this->sliceRootNamespace}\\{$namespace}\\"]);
        config(['livewire.view_path' => "{$this->sliceRootFolder}/{$this->sliceName}/resources/views/{$viewFolder}"]);

        parent::handle();
    }
}
