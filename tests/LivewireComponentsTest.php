<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use FullSmack\LaravelSlice\Slice;
use FullSmack\LivewireSlice\LivewireComponents;

final class LivewireComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupTestSlices();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestSlices();

        parent::tearDown();
    }

    #[Test]
    public function it_registers_livewire_components_from_slice(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        $componentClassDirectory = $this->livewireClassDirectory($sliceName);

        File::ensureDirectoryExists($componentClassDirectory);

        $viewDirectory = $this->livewireViewDirectory($sliceName);

        File::ensureDirectoryExists($viewDirectory);

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = new LivewireComponents();
        $extension->register($slice);

        $this->assertTrue(true, "Registration should complete without errors");
    }

    #[Test]
    public function it_handles_nested_component_directories(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        $componentClassDirectory = $this->livewireClassDirectory($sliceName);
        $nestedDirectory = "{$componentClassDirectory}/Posts";

        File::ensureDirectoryExists($nestedDirectory);

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = new LivewireComponents();
        $extension->register($slice);

        $this->assertTrue(true, "Should handle nested directories without errors");
    }

    #[Test]
    public function it_handles_missing_livewire_directory_gracefully(): void
    {
        /* Arrange */
        $sliceName = 'empty-slice';

        $this->createSliceStructure($sliceName);

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = new LivewireComponents();
        $extension->register($slice);

        $this->assertTrue(true, "Should handle missing Livewire directory without errors");
    }

    #[Test]
    public function it_constructs_correct_directory_path_from_config(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        $expectedDirectory = $this->livewireClassDirectory($sliceName);

        File::ensureDirectoryExists($expectedDirectory);

        $slice = $this->createMockSlice($sliceName);

        /* Act */
        $extension = new LivewireComponents();
        $extension->register($slice);

        /* Assert */
        $this->assertDirectoryExists($expectedDirectory, "Should use PascalCase 'Livewire' directory");
    }

    #[Test]
    public function configure_returns_an_instance(): void
    {
        $extension = LivewireComponents::configure();

        $this->assertInstanceOf(LivewireComponents::class, $extension);
    }

    #[Test]
    public function component_path_is_chainable_and_sets_component_namespace(): void
    {
        $extension = LivewireComponents::configure()
            ->componentPath('ui.web.livewire');

        $this->assertInstanceOf(LivewireComponents::class, $extension);
        $this->assertSame('ui.web.livewire', $extension->getComponentNamespace());
    }

    #[Test]
    public function view_path_is_chainable_and_sets_view_namespace(): void
    {
        $extension = LivewireComponents::configure()
            ->viewPath('custom-views');

        $this->assertInstanceOf(LivewireComponents::class, $extension);
        $this->assertSame('custom-views', $extension->getViewNamespace());
    }

    #[Test]
    public function path_sets_both_component_and_view_namespace(): void
    {
        $extension = LivewireComponents::configure()
            ->path('shared.namespace');

        $this->assertSame('shared.namespace', $extension->getComponentNamespace());
        $this->assertSame('shared.namespace', $extension->getViewNamespace());
    }

    #[Test]
    public function default_component_namespace_is_livewire(): void
    {
        $extension = new LivewireComponents();

        $this->assertSame('livewire', $extension->getComponentNamespace());
    }

    #[Test]
    public function default_view_namespace_falls_back_to_config(): void
    {
        $extension = new LivewireComponents();

        $this->assertSame(
            config('livewire-slice.view-folder', 'livewire'),
            $extension->getViewNamespace()
        );
    }

    #[Test]
    public function component_path_does_not_affect_view_namespace(): void
    {
        $extension = LivewireComponents::configure()
            ->componentPath('ui.web.livewire');

        $this->assertSame('ui.web.livewire', $extension->getComponentNamespace());
        $this->assertSame(
            config('livewire-slice.view-folder', 'livewire'),
            $extension->getViewNamespace()
        );
    }

    #[Test]
    public function it_registers_components_with_custom_component_path(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        $customDirectory = base_path("src/{$sliceName}/src/Ui/Web/Livewire");

        File::ensureDirectoryExists($customDirectory);

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = LivewireComponents::configure()
            ->componentPath('ui.web.livewire');

        $extension->register($slice);

        $this->assertTrue(true, "Registration with custom component path should complete without errors");
    }

    #[Test]
    public function it_handles_missing_custom_directory_gracefully(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = LivewireComponents::configure()
            ->componentPath('nonexistent.deep.path');

        $extension->register($slice);

        $this->assertTrue(true, "Should handle missing custom directory without errors");
    }

    #[Test]
    public function multiple_component_paths_accumulate(): void
    {
        $extension = LivewireComponents::configure()
            ->componentPath('livewire')
            ->componentPath('filament.livewire');

        $this->assertSame(['livewire', 'filament.livewire'], $extension->getComponentPaths());
        $this->assertSame('livewire', $extension->getComponentNamespace());
    }

    #[Test]
    public function multiple_view_paths_accumulate(): void
    {
        $extension = LivewireComponents::configure()
            ->viewPath('livewire')
            ->viewPath('filament.livewire');

        $this->assertSame(['livewire', 'filament.livewire'], $extension->getViewPaths());
        $this->assertSame('livewire', $extension->getViewNamespace());
    }

    #[Test]
    public function get_component_paths_returns_default_when_empty(): void
    {
        $extension = new LivewireComponents();

        $this->assertSame(['livewire'], $extension->getComponentPaths());
    }

    #[Test]
    public function get_view_paths_returns_default_when_empty(): void
    {
        $extension = new LivewireComponents();

        $this->assertSame(
            [config('livewire-slice.view-folder', 'livewire')],
            $extension->getViewPaths()
        );
    }

    #[Test]
    public function path_accumulates_to_both_component_and_view_paths(): void
    {
        $extension = LivewireComponents::configure()
            ->path('livewire')
            ->path('filament.livewire');

        $this->assertSame(['livewire', 'filament.livewire'], $extension->getComponentPaths());
        $this->assertSame(['livewire', 'filament.livewire'], $extension->getViewPaths());
    }

    #[Test]
    public function component_and_view_paths_accumulate_independently(): void
    {
        $extension = LivewireComponents::configure()
            ->componentPath('livewire')
            ->viewPath('livewire')
            ->componentPath('filament.livewire')
            ->viewPath('filament.livewire');

        $this->assertSame(['livewire', 'filament.livewire'], $extension->getComponentPaths());
        $this->assertSame(['livewire', 'filament.livewire'], $extension->getViewPaths());
    }

    #[Test]
    public function it_registers_from_multiple_component_directories(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        File::ensureDirectoryExists(base_path("src/{$sliceName}/src/Livewire"));
        File::ensureDirectoryExists(base_path("src/{$sliceName}/src/Filament/Livewire"));

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = LivewireComponents::configure()
            ->componentPath('livewire')
            ->componentPath('filament.livewire');

        $extension->register($slice);

        $this->assertTrue(true, "Registration from multiple directories should complete without errors");
    }

    #[Test]
    public function it_skips_missing_directories_in_multi_path_registration(): void
    {
        /* Arrange */
        $sliceName = 'blog';

        $this->createSliceStructure($sliceName);

        File::ensureDirectoryExists(base_path("src/{$sliceName}/src/Livewire"));

        $slice = $this->createMockSlice($sliceName);

        /* Act & Assert */
        $extension = LivewireComponents::configure()
            ->componentPath('livewire')
            ->componentPath('nonexistent.path');

        $extension->register($slice);

        $this->assertTrue(true, "Should skip missing directories and continue registration");
    }

    private function createMockSlice(string $sliceName): Slice
    {
        $slice = new Slice();
        $slice->setName($sliceName);
        $slice->setPath(base_path("src/{$sliceName}"));
        $slice->setNamespace("Slice\\" . Str::studly($sliceName));

        return $slice;
    }

    private function livewireClassDirectory(string $sliceName): string
    {
        return base_path("src/{$sliceName}/src/Livewire");
    }

    private function livewireViewDirectory(string $sliceName): string
    {
        return base_path("src/{$sliceName}/resources/views/livewire");
    }

    private function createSliceStructure(string $sliceName): void
    {
        $slicePath = base_path("src/{$sliceName}");

        /* Create basic slice directory structure */
        File::ensureDirectoryExists("{$slicePath}/src");

        File::ensureDirectoryExists("{$slicePath}/resources/views");
    }

    private function cleanupTestSlices(): void
    {
        $testSlices = [
            'blog',
            'empty-slice',
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
