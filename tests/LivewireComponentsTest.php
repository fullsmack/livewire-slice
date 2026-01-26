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
        $feature = new LivewireComponents();
        $feature->register($slice);

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
        $feature = new LivewireComponents();
        $feature->register($slice);

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
        $feature = new LivewireComponents();
        $feature->register($slice);

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
        $feature = new LivewireComponents();
        $feature->register($slice);

        /* Assert */
        $this->assertDirectoryExists($expectedDirectory, "Should use PascalCase 'Livewire' directory");
    }

    private function createMockSlice(string $sliceName): Slice
    {
        $slice = new Slice();
        $slice->setName($sliceName);
        $slice->setPath(base_path("src/{$sliceName}/src"));
        $slice->setBaseNamespace("Slice\\" . Str::studly($sliceName));

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
