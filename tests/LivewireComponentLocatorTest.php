<?php
declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

use FullSmack\LaravelSlice\Path;
use FullSmack\LaravelSlice\Slice;
use FullSmack\LivewireSlice\LivewireComponentLocator;

final class LivewireComponentLocatorTest extends TestCase
{
    #[Test]
    public function it_builds_default_slice_livewire_locations(): void
    {
        /* Arrange */
        $slice = $this->makeSlice('sample');
        $locator = $this->app->make(LivewireComponentLocator::class);

        /* Assert */
        $this->assertSame('Livewire', $locator->relativeClassNamespace());
        $this->assertSame('Livewire', $locator->relativeClassPath());
        $this->assertSame(
            Path::normalize(base_path('src/sample/src/Livewire')),
            Path::normalize($locator->classPathForSlice($slice))
        );
        $this->assertSame('Slice\\Sample\\Livewire', $locator->classNamespaceForSlice($slice));
        $this->assertSame(
            Path::normalize(base_path('src/sample/resources/views/livewire')),
            Path::normalize($locator->viewPathForSlice($slice))
        );
        $this->assertSame('sample::livewire.example-action-button', $locator->viewName('sample', 'ExampleActionButton'));
    }

    #[Test]
    public function it_builds_custom_slice_livewire_locations_from_config(): void
    {
        /* Arrange */
        config()->set('livewire-slice.namespace', 'widgets.forms');
        config()->set('livewire-slice.view-folder', 'ui/livewire');

        $slice = $this->makeSlice('sample');
        $locator = $this->app->make(LivewireComponentLocator::class);

        /* Assert */
        $this->assertSame('Widgets\\Forms', $locator->relativeClassNamespace());
        $this->assertSame('Widgets/Forms', $locator->relativeClassPath());
        $this->assertSame(
            Path::normalize(base_path('src/sample/src/Widgets/Forms')),
            Path::normalize($locator->classPathForSlice($slice))
        );
        $this->assertSame('Slice\\Sample\\Widgets\\Forms', $locator->classNamespaceForSlice($slice));
        $this->assertSame(
            Path::normalize(base_path('src/sample/resources/views/ui/livewire')),
            Path::normalize($locator->viewPathForSlice($slice))
        );
        $this->assertSame('sample::ui/livewire.example-action-button', $locator->viewName('sample', 'ExampleActionButton'));
    }

    #[Test]
    public function it_normalizes_component_names_for_aliases_and_classes(): void
    {
        /* Arrange */
        $locator = $this->app->make(LivewireComponentLocator::class);

        /* Assert */
        $this->assertSame('panels.example-action-button', $locator->normalizeComponentName('Panels/ExampleActionButton'));
        $this->assertSame('Panels\\ExampleActionButton', $locator->classSuffix('panels.example-action-button'));
    }

    private function makeSlice(string $sliceName): Slice
    {
        $slice = new Slice();
        $slice->setName($sliceName);
        $slice->setPath(base_path('src/' . str_replace('.', '/', $sliceName)));
        $slice->setNamespace('Slice\\' . Str::of($sliceName)->explode('.')->map(Str::studly(...))->implode('\\'));

        return $slice;
    }
}
