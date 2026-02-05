<?php
declare(strict_types=1);

namespace Tests;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use FullSmack\LivewireSlice\ComponentParserUsingCustomLocation;

/**
 * Tests for ComponentParserUsingCustomLocation.
 *
 * Note: The parser now receives all paths explicitly from the caller (MakeLivewire command)
 * instead of deriving them from the namespace. This makes it simpler and more flexible,
 * allowing custom slice configurations without relying on config values or static methods.
 */
final class ComponentParserUsingCustomLocationTest extends TestCase
{
    private function createParser(
        string $classNamespace = 'Slice\Blog\Livewire',
        string $classPath = '/app/src/blog/src/Livewire',
        string $viewPath = '/app/src/blog/resources/views/livewire',
        string $testPath = '/app/src/blog/tests/Livewire',
        string $testNamespace = 'Slice\Blog\Tests\Livewire',
        string $rawComponentName = 'PostList',
        string $sliceName = 'blog',
        ?string $stubSubDirectory = null,
        ?string $viewFolder = null,
    ): ComponentParserUsingCustomLocation
    {
        return new ComponentParserUsingCustomLocation(
            classNamespace: $classNamespace,
            classPath: $classPath,
            viewPath: $viewPath,
            testPath: $testPath,
            testNamespace: $testNamespace,
            rawComponentName: $rawComponentName,
            sliceName: $sliceName,
            stubSubDirectory: $stubSubDirectory,
            viewFolder: $viewFolder,
        );
    }

    #[Test]
    public function it_stores_class_namespace(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(classNamespace: 'Slice\Blog\Livewire');

        /* Assert */
        $this->assertEquals('Slice\Blog\Livewire', $parser->classNamespace());
    }

    #[Test]
    public function it_builds_full_class_path(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            classPath: '/app/src/blog/src/Livewire',
            rawComponentName: 'PostList',
        );

        /* Assert */
        $this->assertEquals('/app/src/blog/src/Livewire/PostList.php', $parser->classPath());
    }

    #[Test]
    public function it_builds_full_view_path(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            viewPath: '/app/src/blog/resources/views/livewire',
            rawComponentName: 'PostList',
        );

        /* Assert */
        $this->assertEquals('/app/src/blog/resources/views/livewire/post-list.blade.php', $parser->viewPath());
    }

    #[Test]
    public function it_builds_full_test_path(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            testPath: '/app/src/blog/tests/Livewire',
            rawComponentName: 'PostList',
        );

        /* Assert */
        $this->assertEquals('/app/src/blog/tests/Livewire/PostListTest.php', $parser->testPath());
    }

    #[Test]
    public function it_stores_test_namespace(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(testNamespace: 'Slice\Blog\Tests\Livewire');

        /* Assert */
        $this->assertEquals('Slice\Blog\Tests\Livewire', $parser->testNamespace());
    }

    #[Test]
    public function it_parses_component_name(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(rawComponentName: 'PostList');

        /* Assert */
        $this->assertEquals('post-list', $parser->component());
        $this->assertEquals('PostList', $parser->className());
    }

    #[Test]
    public function it_parses_nested_component_name(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(rawComponentName: 'Posts/CommentList');

        /* Assert */
        $this->assertEquals('comment-list', $parser->component());
        $this->assertEquals('CommentList', $parser->className());
    }

    #[Test]
    public function it_builds_nested_class_path(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            classPath: '/app/src/blog/src/Livewire',
            rawComponentName: 'Posts/CommentList',
        );

        /* Assert */
        $this->assertEquals('/app/src/blog/src/Livewire/Posts/CommentList.php', $parser->classPath());
    }

    #[Test]
    public function it_builds_nested_view_path(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            viewPath: '/app/src/blog/resources/views/livewire',
            rawComponentName: 'Posts/CommentList',
        );

        /* Assert */
        // View paths use DIRECTORY_SEPARATOR, so we check the parts are correct
        $viewPath = $parser->viewPath();
        $this->assertStringStartsWith('/app/src/blog/resources/views/livewire/', $viewPath);
        $this->assertStringEndsWith('comment-list.blade.php', $viewPath);
        $this->assertStringContainsString('posts', $viewPath);
    }

    #[Test]
    public function it_builds_nested_class_namespace(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            classNamespace: 'Slice\Blog\Livewire',
            rawComponentName: 'Posts/CommentList',
        );

        /* Assert */
        $this->assertEquals('Slice\Blog\Livewire\Posts', $parser->classNamespace());
    }

    #[Test]
    public function it_generates_view_name_with_slice_prefix(): void
    {
        /* Arrange */
        config(['livewire-slice.view-folder' => 'livewire']);

        /* Act */
        $parser = $this->createParser(
            rawComponentName: 'PostList',
            sliceName: 'blog',
        );

        /* Assert */
        $this->assertEquals('blog::livewire.post-list', $parser->viewName());
    }

    #[Test]
    public function it_generates_view_name_for_nested_component(): void
    {
        /* Arrange */
        config(['livewire-slice.view-folder' => 'livewire']);

        /* Act */
        $parser = $this->createParser(
            rawComponentName: 'Posts/CommentList',
            sliceName: 'blog',
        );

        /* Assert */
        $this->assertEquals('blog::livewire.posts.comment-list', $parser->viewName());
    }

    #[Test]
    public function it_generates_view_name_for_nested_slice(): void
    {
        /* Arrange */
        config(['livewire-slice.view-folder' => 'livewire']);

        /* Act */
        $parser = $this->createParser(
            rawComponentName: 'CreatePost',
            sliceName: 'api.posts',
        );

        /* Assert */
        $this->assertEquals('api.posts::livewire.create-post', $parser->viewName());
    }

    #[Test]
    public function it_uses_custom_view_folder_when_provided(): void
    {
        /* Arrange */
        config(['livewire-slice.view-folder' => 'livewire']);

        /* Act */
        $parser = $this->createParser(
            rawComponentName: 'PostList',
            sliceName: 'blog',
            viewFolder: 'filament.livewire',
        );

        /* Assert */
        $this->assertEquals('blog::filament.livewire.post-list', $parser->viewName());
    }

    #[Test]
    public function it_falls_back_to_config_when_view_folder_is_null(): void
    {
        /* Arrange */
        config(['livewire-slice.view-folder' => 'livewire']);

        /* Act */
        $parser = $this->createParser(
            rawComponentName: 'PostList',
            sliceName: 'blog',
            viewFolder: null,
        );

        /* Assert */
        $this->assertEquals('blog::livewire.post-list', $parser->viewName());
    }

    #[Test]
    public function it_uses_custom_view_folder_for_nested_component(): void
    {
        /* Arrange & Act */
        $parser = $this->createParser(
            rawComponentName: 'Posts/CommentList',
            sliceName: 'blog',
            viewFolder: 'filament.livewire',
        );

        /* Assert */
        $this->assertEquals('blog::filament.livewire.posts.comment-list', $parser->viewName());
    }
}
