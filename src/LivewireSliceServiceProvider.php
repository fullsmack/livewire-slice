<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Illuminate\Support\ServiceProvider;

use FullSmack\LivewireSlice\Command\MakeLivewire;
use FullSmack\LivewireSlice\Command\MakeLivewireV4;

class LivewireSliceServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMissingComponentResolver();

        $this->registerConfig();

        $this->publishesConfig();

        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\FullSmack\LivewireSlice\LivewireComponentLocator::class);
        $this->app->singleton(\FullSmack\LivewireSlice\LivewireSliceRegistry::class);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ .'/../config/livewire-slice.php', 'livewire-slice');
    }

    protected function publishesConfig()
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__ .'/../config/livewire-slice.php' => config_path('livewire-slice.php'),
            ], 'config');

        }
    }

    protected function registerCommands(): void
    {
        if($this->app->runningInConsole())
        {
            // Livewire v4 introduced the Finder class and rewrote MakeCommand.
            // Use the appropriate command implementation based on the installed version.
            $command = class_exists('Livewire\\Finder\\Finder')
                ? MakeLivewireV4::class
                : MakeLivewire::class;

            $this->commands([$command]);
        }
    }

    protected function registerMissingComponentResolver(): void
    {
        if (! $this->app->bound('livewire'))
        {
            return;
        }

        $this->app['livewire']->resolveMissingComponent(
            $this->app->make(\FullSmack\LivewireSlice\LivewireSliceRegistry::class)
        );
    }
}
