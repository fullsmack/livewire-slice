<?php
declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Str;
use FullSmack\LivewireSlice\LivewireSliceRegistry;
use FullSmack\LivewireSlice\LivewireSliceServiceProvider;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends Orchestra
{
    private static bool $sliceAutoloaderRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSliceAutoloader();
        $this->app->make(LivewireSliceRegistry::class)->clear();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            LivewireSliceServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default laravel-slice config for testing
        config()->set('laravel-slice', [
            'root' => [
                'folder' => 'src',
                'namespace' => 'Slice',
                'namespace-mode' => 'prefix',
            ],
            'test' => [
                'namespace' => 'Tests',
            ],
        ]);

        // Setup livewire-slice config
        config()->set('livewire-slice', [
            'namespace' => 'livewire',
            'view-folder' => 'livewire',
        ]);

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    private function registerSliceAutoloader(): void
    {
        if (self::$sliceAutoloaderRegistered)
        {
            return;
        }

        spl_autoload_register(static function (string $class): void {
            if (! str_starts_with($class, 'Slice\\'))
            {
                return;
            }

            $segments = explode('\\', substr($class, strlen('Slice\\')));
            $livewireSegments = Str::of(config('livewire-slice.namespace', 'livewire'))
                ->explode('.')
                ->map(Str::studly(...))
                ->all();

            if (count($segments) <= count($livewireSegments))
            {
                return;
            }

            $livewireOffset = null;

            for ($index = 0; $index <= count($segments) - count($livewireSegments) - 1; $index++)
            {
                if (array_slice($segments, $index, count($livewireSegments)) === $livewireSegments)
                {
                    $livewireOffset = $index;

                    break;
                }
            }

            if ($livewireOffset === null || $livewireOffset === 0)
            {
                return;
            }

            $sliceSegments = array_slice($segments, 0, $livewireOffset);
            $classSegments = array_slice($segments, $livewireOffset);

            $path = base_path(
                'src/'
                . implode('/', array_map(static fn (string $segment): string => Str::kebab($segment), $sliceSegments))
                . '/src/'
                . implode('/', $classSegments)
                . '.php'
            );

            if (is_file($path))
            {
                require_once $path;
            }
        });

        self::$sliceAutoloaderRegistered = true;
    }
}
