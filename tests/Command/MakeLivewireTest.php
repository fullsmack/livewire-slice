<?php
declare(strict_types=1);

namespace Tests\Command;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use FullSmack\LaravelSlice\Slice;
use FullSmack\LaravelSlice\SliceRegistry;

final class MakeLivewireTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any test files created during testing
        $this->cleanupTestSlices();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestSlices();
        SliceRegistry::clear();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_livewire_component_in_flat_slice_with_correct_namespace(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'PostList';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace Slice\Blog\Livewire;', $classContent);
        $this->assertStringContainsString("class {$componentName} extends Component", $classContent);
    }

    #[Test]
    public function it_generates_livewire_component_in_nested_slice_with_correct_namespace(): void
    {
        /* Arrange */
        $slicePath = 'api/posts';
        $sliceName = 'api.posts';
        $componentName = 'CreatePost';
        $this->createSliceStructure($slicePath);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/api/posts/src/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace Slice\Api\Posts\Livewire;', $classContent);
        $this->assertStringContainsString("class {$componentName} extends Component", $classContent);
    }

    #[Test]
    public function it_generates_view_file_in_flat_slice_with_correct_path(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'PostList';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedViewPath = base_path("src/{$sliceName}/resources/views/livewire/post-list.blade.php");
        $this->assertFileExists($expectedViewPath);

        $viewContent = File::get($expectedViewPath);
        $this->assertStringContainsString('<div>', $viewContent);
    }

    #[Test]
    public function it_generates_view_file_in_nested_slice_with_correct_path(): void
    {
        /* Arrange */
        $sliceName = 'api.posts';
        $slicePath = 'api/posts';
        $componentName = 'CreatePost';
        $this->createSliceStructure($slicePath);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedViewPath = base_path("src/api/posts/resources/views/livewire/create-post.blade.php");
        $this->assertFileExists($expectedViewPath);
    }

    #[Test]
    #[TestWith(['blog', 'blog', 'Slice\Blog\Livewire'], 'flat slice')]
    #[TestWith(['api/posts', 'api.posts', 'Slice\Api\Posts\Livewire'], 'nested slice with two levels')]
    #[TestWith(['admin/api/users', 'admin.api.users', 'Slice\Admin\Api\Users\Livewire'], 'deeply nested slice')]
    public function it_derives_correct_psr4_namespace_for_different_slice_structures(
        string $slicePath,
        string $sliceName,
        string $expectedNamespace
    ): void {
        /* Arrange */
        $componentName = 'TestComponent';
        $this->createSliceStructure($slicePath);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $slicePath = str_replace('/', DIRECTORY_SEPARATOR, $slicePath);
        $expectedClassPath = base_path("src/{$slicePath}/src/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString("namespace {$expectedNamespace};", $classContent);
    }

    #[Test]
    #[TestWith(['payments', 'integrations/stripe', 'Vendor\Stripe\Payments', 'Vendor\Stripe\Payments\Livewire'], 'vendor-style namespace')]
    #[TestWith(['user-profile', 'users/profile', 'App\Domain\UserProfile', 'App\Domain\UserProfile\Livewire'], 'custom domain namespace')]
    #[TestWith(['acme.billing', 'billing', 'Acme\Billing\Subscriptions', 'Acme\Billing\Subscriptions\Livewire'], 'dotted name with custom namespace')]
    public function it_preserves_custom_namespace_from_registry(
        string $sliceName,
        string $slicePath,
        string $customNamespace,
        string $expectedLivewireNamespace
    ): void
    {
        /* Arrange */
        $componentName = 'TestComponent';
        $this->registerSliceWithCustomNamespace($sliceName, $slicePath, $customNamespace);

        // Debug: verify slice is registered
        $this->assertTrue(SliceRegistry::has($sliceName), "Slice '{$sliceName}' should be registered");
        $registeredSlice = SliceRegistry::get($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->expectsOutputToContain('COMPONENT CREATED')
          ->assertSuccessful();

        /* Assert */
        // Use the registered slice's actual path
        $expectedClassPath = $registeredSlice->sourcePath('Livewire') . DIRECTORY_SEPARATOR . "{$componentName}.php";
        $this->assertFileExists($expectedClassPath, "Component should be created at: {$expectedClassPath}");

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString("namespace {$expectedLivewireNamespace};", $classContent);
    }

    #[Test]
    public function it_generates_nested_component_with_correct_namespace(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'Posts/CommentList';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Livewire/Posts/CommentList.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace Slice\Blog\Livewire\Posts;', $classContent);
        $this->assertStringContainsString('class CommentList extends Component', $classContent);
    }

    #[Test]
    public function it_generates_inline_component_without_view_file(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'InlineComponent';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
            '--inline' => true,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $expectedViewPath = base_path("src/{$sliceName}/resources/views/livewire/inline-component.blade.php");
        $this->assertFileDoesNotExist($expectedViewPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('public function render()', $classContent);
        $this->assertStringContainsString('<<<\'HTML\'', $classContent);
    }

    #[Test]
    public function it_falls_back_to_default_livewire_behavior_when_no_slice_option_provided(): void
    {
        /* Arrange */
        $componentName = 'DefaultComponent';

        // Set up default Livewire paths
        config(['livewire.class_namespace' => 'App\\Livewire']);
        config(['livewire.view_path' => resource_path('views/livewire')]);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = app_path("Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace App\Livewire;', $classContent);
    }

    #[Test]
    public function it_uses_slice_root_namespace_from_slice_definitions(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'TestComponent';

        // Modify the slice root namespace BEFORE creating the slice
        config(['laravel-slice.root.namespace' => 'CustomSlice']);

        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Livewire/{$componentName}.php");
        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace CustomSlice\Blog\Livewire;', $classContent);
    }

    #[Test]
    public function it_generates_component_at_custom_component_path(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'PostList';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
            '--component-path' => 'filament.livewire',
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Filament/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace Slice\Blog\Filament\Livewire;', $classContent);
        $this->assertStringContainsString("class {$componentName} extends Component", $classContent);
    }

    #[Test]
    public function it_generates_view_at_custom_view_path(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'PostList';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
            '--view-path' => 'filament.livewire',
        ])->assertSuccessful();

        /* Assert */
        $expectedViewPath = base_path("src/{$sliceName}/resources/views/filament/livewire/post-list.blade.php");
        $this->assertFileExists($expectedViewPath);
    }

    #[Test]
    public function it_generates_component_and_view_at_custom_paths(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'ActionPanel';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
            '--component-path' => 'filament.livewire',
            '--view-path' => 'filament.livewire',
        ])->assertSuccessful();

        /* Assert */
        $expectedClassPath = base_path("src/{$sliceName}/src/Filament/Livewire/{$componentName}.php");
        $this->assertFileExists($expectedClassPath);

        $classContent = File::get($expectedClassPath);
        $this->assertStringContainsString('namespace Slice\Blog\Filament\Livewire;', $classContent);

        $expectedViewPath = base_path("src/{$sliceName}/resources/views/filament/livewire/action-panel.blade.php");
        $this->assertFileExists($expectedViewPath);
    }

    #[Test]
    public function it_uses_custom_view_folder_in_generated_view_reference(): void
    {
        /* Arrange */
        $sliceName = 'blog';
        $componentName = 'ActionPanel';
        $this->createSliceStructure($sliceName);

        /* Act */
        $this->artisan('livewire:make', [
            'name' => $componentName,
            '--slice' => $sliceName,
            '--component-path' => 'filament.livewire',
            '--view-path' => 'filament.livewire',
        ])->assertSuccessful();

        /* Assert */
        $classContent = File::get(
            base_path("src/{$sliceName}/src/Filament/Livewire/{$componentName}.php")
        );
        $this->assertStringContainsString('filament.livewire.action-panel', $classContent);
    }

    /**
     * Helper method to create the basic slice directory structure
     */
    private function createSliceStructure(string $sliceName): void
    {
        $slicePath = base_path("src/{$sliceName}");
        File::makeDirectory($slicePath, 0755, true);
        File::makeDirectory("{$slicePath}/src", 0755, true);
        File::makeDirectory("{$slicePath}/resources/views", 0755, true);

        // Build the namespace from the slice path using configured root namespace
        $rootNamespace = Str::studly(config('laravel-slice.root.namespace', 'Slice'));
        $namespaceSegments = array_map([Str::class, 'studly'], explode('/', $sliceName));
        $namespace = $rootNamespace . '\\' . implode('\\', $namespaceSegments);

        // Build the slice identifier (dot notation)
        $identifier = str_replace('/', '.', $sliceName);

        // Register the slice in the registry
        $slice = new Slice();
        $slice->setName($identifier);
        $slice->setPath($slicePath);
        $slice->setNamespace($namespace);

        SliceRegistry::register($slice);
    }

    /**
     * Helper method to register a slice with custom (non-conventional) namespace.
     */
    private function registerSliceWithCustomNamespace(
        string $sliceName,
        string $slicePath,
        string $customNamespace
    ): void {
        $absolutePath = base_path("src/{$slicePath}");
        File::makeDirectory($absolutePath, 0755, true);
        File::makeDirectory("{$absolutePath}/src", 0755, true);
        File::makeDirectory("{$absolutePath}/resources/views", 0755, true);

        $slice = new Slice();
        $slice->setName($sliceName);
        $slice->setPath($absolutePath);
        $slice->setNamespace($customNamespace);

        SliceRegistry::register($slice);
    }

    /**
     * Helper method to clean up test slices
     */
    private function cleanupTestSlices(): void
    {
        $testSlices = [
            'blog',
            'api',
            'admin',
            'integrations',
            'users',
            'billing',
        ];

        foreach ($testSlices as $slice) {
            $path = base_path("src/{$slice}");
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        // Clean up default Livewire files if they exist
        $defaultPath = app_path('Livewire');
        if (File::exists($defaultPath)) {
            File::deleteDirectory($defaultPath);
        }
    }
}
