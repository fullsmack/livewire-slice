<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Illuminate\Support\ServiceProvider;

use FullSmack\LivewireSlice\Command\MakeLivewire;

class LivewireSliceServiceProvider extends ServiceProvider
{
    protected $commands = [
        MakeLivewire::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
        //
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
            $this->commands($this->commands);
        }
    }
}
