<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice\Command;

use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;

use FullSmack\LaravelSlice\Command\SliceDefinitions;

class MakeLivewire extends MakeCommand
{
    use SliceDefinitions;

    protected $signature = 'make:livewire {name} {--force} {--inline} {--test} {--pest} {--stub=}';

    public function handle()
    {
        $this->defineSliceUsingOption();

        config(['livewire.class_namespace' => "{$this->sliceRootNamespace}\\Livewire\\"]);
        config(['livewire.view_path' => "{$this->sliceRootFolder}/{$this->sliceName}/resources/views/livewire"]);

        parent::handle();
    }
}
