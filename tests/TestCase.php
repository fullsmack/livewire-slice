<?php
declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use FullSmack\LivewireSlice\LivewireSliceServiceProvider;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends Orchestra
{
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
    }
}
