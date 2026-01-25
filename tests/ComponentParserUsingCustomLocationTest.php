<?php
declare(strict_types=1);

namespace Tests;

use Tests\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use FullSmack\LivewireSlice\ComponentParserUsingCustomLocation;

final class ComponentParserUsingCustomLocationTest extends TestCase
{
    #[Test]
    #[TestWith(['Slice\Blog\Livewire', 'blog', 'src/blog/src/Livewire'], 'flat slice')]
    #[TestWith(['Slice\Api\Posts\Livewire', 'api/posts', 'src/api/posts/src/Livewire'], 'nested slice')]
    #[TestWith(['Slice\Admin\Api\Users\Livewire', 'admin/api/users', 'src/admin/api/users/src/Livewire'], 'deeply nested slice')]
    public function it_generates_correct_path_from_namespace(
        string $namespace,
        string $sliceName,
        string $expectedPathSuffix
    ): void {
        /* Arrange */
        config(['laravel-slice.root.namespace' => 'Slice']);
        config(['laravel-slice.root.folder' => 'src']);

        /* Act */
        $generatedPath = ComponentParserUsingCustomLocation::generatePathFromNamespace($namespace);

        /* Assert */
        $expectedPath = base_path($expectedPathSuffix);
        $this->assertEquals($expectedPath, $generatedPath);
    }

    #[Test]
    public function it_generates_correct_path_with_custom_root_namespace(): void
    {
        /* Arrange */
        config(['laravel-slice.root.namespace' => 'CustomSlice']);
        config(['laravel-slice.root.folder' => 'modules']);
        $namespace = 'CustomSlice\Blog\Livewire';

        /* Act */
        $generatedPath = ComponentParserUsingCustomLocation::generatePathFromNamespace($namespace);

        /* Assert */
        $expectedPath = base_path('modules/blog/src/Livewire');
        $this->assertEquals($expectedPath, $generatedPath);
    }

    #[Test]
    #[TestWith(['Slice\Blog\Livewire', 'blog', 'src/blog/tests'], 'flat slice')]
    #[TestWith(['Slice\Api\Posts\Livewire', 'api/posts', 'src/api/posts/tests'], 'nested slice')]
    public function it_generates_correct_test_path_from_namespace(
        string $namespace,
        string $sliceName,
        string $expectedPathSuffix
    ): void {
        /* Arrange */
        config(['laravel-slice.root.folder' => 'src']);
        config(['laravel-slice.test.namespace' => 'Tests']);

        /* Act */
        $generatedPath = ComponentParserUsingCustomLocation::generateTestPathFromNamespace($namespace);

        /* Assert */
        $expectedPath = base_path($expectedPathSuffix);
        $this->assertEquals($expectedPath, $generatedPath);
    }

    #[Test]
    public function it_generates_view_name_with_slice_prefix(): void
    {
        /* Arrange */
        config(['laravel-slice.root.folder' => 'src']);
        config(['livewire-slice.view-folder' => 'livewire']);

        $namespace = 'Slice\Blog\Livewire';
        $viewPath = base_path('src/blog/resources/views/livewire');
        $componentName = 'post-list';

        $parser = new ComponentParserUsingCustomLocation(
            $namespace,
            $viewPath,
            'PostList',
            null
        );

        /* Act */
        $viewName = $parser->viewName();

        /* Assert */
        $this->assertEquals('blog::livewire.post-list', $viewName);
    }

    #[Test]
    public function it_generates_view_name_for_nested_component(): void
    {
        /* Arrange */
        config(['laravel-slice.root.folder' => 'src']);
        config(['livewire-slice.view-folder' => 'livewire']);

        $namespace = 'Slice\Blog\Livewire\Posts';
        $viewPath = base_path('src/blog/resources/views/livewire');

        $parser = new ComponentParserUsingCustomLocation(
            $namespace,
            $viewPath,
            'Posts/CommentList',
            null
        );

        /* Act */
        $viewName = $parser->viewName();

        /* Assert */
        $this->assertEquals('blog::livewire.posts.comment-list', $viewName);
    }

    #[Test]
    public function it_generates_view_name_for_nested_slice(): void
    {
        /* Arrange */
        config(['laravel-slice.root.folder' => 'src']);
        config(['livewire-slice.view-folder' => 'livewire']);

        $namespace = 'Slice\Api\Posts\Livewire';
        $viewPath = base_path('src/api/posts/resources/views/livewire');

        $parser = new ComponentParserUsingCustomLocation(
            $namespace,
            $viewPath,
            'CreatePost',
            null
        );

        /* Act */
        $viewName = $parser->viewName();

        /* Assert */
        $this->assertEquals('api/posts::livewire.create-post', $viewName);
    }
}
