<?php
declare(strict_types=1);

namespace Tests;

use Livewire\Livewire;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\File;
use Livewire\Exceptions\ComponentNotFoundException;

use FullSmack\LaravelSlice\Slice;
use FullSmack\LivewireSlice\LivewireComponents;
use FullSmack\LivewireSlice\LivewireSliceRegistry;
use FullSmack\LivewireSlice\LivewireComponentLocator;

final class LivewireComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupTestSlices();
    }

    protected function tearDown(): void
    {
        $this->app->make(LivewireSliceRegistry::class)->clear();
        $this->cleanupTestSlices();

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_registered_slice_component_aliases_at_runtime(): void
    {
        /* Arrange */
        $slice = $this->createMockSlice('sample');

        $this->createLivewireComponent($slice, 'RuntimeWidgetButton', 'Sample widget');

        $extension = new LivewireComponents();
        $extension->register($slice);

        /* Act */
        $component = Livewire::new('sample::runtime-widget-button');

        /* Assert */
        $this->assertSame('sample::runtime-widget-button', $component->getName());
        $this->assertInstanceOf('Slice\\Sample\\Livewire\\RuntimeWidgetButton', $component);
    }

    #[Test]
    public function it_resolves_nested_slice_component_aliases_at_runtime(): void
    {
        /* Arrange */
        $slice = $this->createMockSlice('sample');

        $this->createLivewireComponent($slice, 'Panels/NestedRuntimeWidgetButton', 'Nested sample widget');

        $extension = new LivewireComponents();
        $extension->register($slice);

        /* Act */
        $component = Livewire::new('sample::panels.nested-runtime-widget-button');

        /* Assert */
        $this->assertSame('sample::panels.nested-runtime-widget-button', $component->getName());
        $this->assertInstanceOf('Slice\\Sample\\Livewire\\Panels\\NestedRuntimeWidgetButton', $component);
    }

    #[Test]
    public function it_supports_livewire_testing_with_registered_slice_aliases(): void
    {
        /* Arrange */
        $slice = $this->createMockSlice('sample');

        $this->createLivewireComponent($slice, 'TestHarnessWidgetButton', 'Rendered from test');

        $extension = new LivewireComponents();
        $extension->register($slice);

        /* Act & Assert */
        Livewire::test('sample::test-harness-widget-button')
            ->assertSee('Rendered from test');
    }

    #[Test]
    public function it_does_not_resolve_components_for_unregistered_slices(): void
    {
        /* Arrange */
        $slice = $this->createMockSlice('sample');

        $this->createLivewireComponent($slice, 'UnregisteredWidgetButton', 'Unregistered');

        $this->expectException(ComponentNotFoundException::class);

        /* Act */
        Livewire::new('sample::unregistered-widget-button');
    }

    #[Test]
    public function it_uses_configured_livewire_namespace_when_resolving_components(): void
    {
        /* Arrange */
        config()->set('livewire-slice.namespace', 'widgets.forms');

        $slice = $this->createMockSlice('custom');

        $this->createLivewireComponent($slice, 'SubmitButton', 'Custom namespace');

        $extension = new LivewireComponents();
        $extension->register($slice);

        /* Act */
        $component = Livewire::new('custom::submit-button');

        /* Assert */
        $this->assertInstanceOf('Slice\\Custom\\Widgets\\Forms\\SubmitButton', $component);
    }

    private function createMockSlice(string $sliceName): Slice
    {
        $slice = new Slice();
        $slice->setName($sliceName);
        $slice->setPath(base_path('src/' . str_replace('.', '/', $sliceName)));
        $slice->setNamespace(
            'Slice\\' . Str::of($sliceName)
                ->explode('.')
                ->map(Str::studly(...))
                ->implode('\\')
        );

        return $slice;
    }

    private function createLivewireComponent(Slice $slice, string $componentName, string $text): void
    {
        $locator = $this->app->make(LivewireComponentLocator::class);
        $baseNamespace = $locator->classNamespaceForSlice($slice);
        $classSuffix = $locator->classSuffix($componentName);
        $segments = explode('\\', $classSuffix);
        $className = array_pop($segments);
        $namespace = empty($segments)
            ? $baseNamespace
            : $baseNamespace . '\\' . implode('\\', $segments);

        $directory = $locator->classPathForSlice($slice);

        if (! empty($segments))
        {
            $directory .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        }

        File::ensureDirectoryExists($directory);
        File::ensureDirectoryExists($locator->viewPathForSlice($slice));

        $contents = <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace};

use Livewire\Component;

final class {$className} extends Component
{
    public function render()
    {
        return <<<'HTML'
<div>{$text}</div>
HTML;
    }
}
PHP;

        File::put($directory . DIRECTORY_SEPARATOR . $className . '.php', $contents);
    }

    private function cleanupTestSlices(): void
    {
        $testSlices = [
            'sample',
            'custom',
        ];

        foreach ($testSlices as $slice)
        {
            $path = base_path("src/{$slice}");

            if (File::exists($path))
            {
                File::deleteDirectory($path);
            }
        }
    }
}
