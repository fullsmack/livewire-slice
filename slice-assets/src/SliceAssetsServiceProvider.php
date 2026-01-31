<?php

declare(strict_types=1);

namespace FullSmack\SliceAssets;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

use FullSmack\SliceAssets\Command\DiscoverSliceAssets;

/**
 * Service provider that bootstraps the slice-assets package.
 *
 * Responsibilities:
 * - Registers the AssetRegistry as a singleton
 * - Binds the SliceAssets extension with config-driven constructor args
 * - Registers configuration
 * - Registers the @sliceVite Blade directive
 * - Registers the artisan command for manifest generation
 */
class SliceAssetsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConfig();
        $this->publishesConfig();
        $this->registerBladeDirectives();
        $this->registerCommands();
    }

    public function register(): void
    {
        // AssetRegistry is a singleton: a single instance collects entry points
        // from all slices and is then used by Blade directives and commands.
        $this->app->singleton(AssetRegistry::class);

        // Bind the Extension so Laravel Slice can resolve it from the container
        // with the correct config values injected.
        $this->app->bind(SliceAssets::class, function ($app) {
            return new SliceAssets(
                registry: $app->make(AssetRegistry::class),
                filesystem: $app->make(Filesystem::class),
                assetsDirectory: config('slice-assets.assets_directory', 'resources'),
                entryPoints: config('slice-assets.entry_points', []),
                basePath: base_path(),
            );
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/slice-assets.php', 'slice-assets');
    }

    protected function publishesConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/slice-assets.php' => config_path('slice-assets.php'),
            ], 'config');
        }
    }

    /**
     * Register the @sliceVite Blade directive.
     *
     * Usage in Blade templates:
     *
     *   @sliceVite('blog')
     *   @sliceVite('admin', 'js/admin.js')
     *
     * The first form loads all registered entry points for the slice.
     * The second form loads a specific entry point from the slice's assets directory.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('sliceVite', function (string $expression): string {
            return "<?php echo \\FullSmack\\SliceAssets\\SliceAssetsServiceProvider::renderSliceVite({$expression}); ?>";
        });
    }

    /**
     * Render the Vite tags for a slice's assets.
     *
     * Called by the @sliceVite Blade directive at runtime.
     * Delegates to Laravel's Vite facade to generate the correct <script> and
     * <link> tags (with HMR support during development).
     *
     * Static so the compiled Blade template can call it without resolving
     * the service provider from the container.
     *
     * @param string $sliceName  The slice to load assets for
     * @param string|null $entryPoint  Optional specific entry point (relative to slice assets dir)
     * @return HtmlString|string  HTML script/link tags
     */
    public static function renderSliceVite(string $sliceName, ?string $entryPoint = null): HtmlString|string
    {
        $registry = app(AssetRegistry::class);
        $buildDirectory = config('slice-assets.build_directory', 'build');

        if ($entryPoint !== null) {
            // Load a specific entry point from the slice
            $sliceRoot = static::resolveSliceAssetsPath($sliceName);

            if ($sliceRoot === null) {
                return '';
            }

            return Vite::useBuildDirectory($buildDirectory)
                ->withEntryPoints([$sliceRoot . '/' . $entryPoint]);
        }

        // Load all registered entry points for the slice
        $entryPoints = $registry->getEntryPoints($sliceName);

        if (empty($entryPoints)) {
            return '';
        }

        return Vite::useBuildDirectory($buildDirectory)
            ->withEntryPoints($entryPoints);
    }

    /**
     * Resolve the assets directory path for a slice (relative to project root).
     *
     * Derives this from the first registered entry point:
     *   "slices/blog/resources/js/app.js" => "slices/blog/resources"
     */
    private static function resolveSliceAssetsPath(string $sliceName): ?string
    {
        $registry = app(AssetRegistry::class);
        $entryPoints = $registry->getEntryPoints($sliceName);

        if (empty($entryPoints)) {
            return null;
        }

        $assetsDirectory = config('slice-assets.assets_directory', 'resources');
        $firstEntry = $entryPoints[0];
        $pos = strpos($firstEntry, '/' . $assetsDirectory . '/');

        if ($pos === false) {
            return null;
        }

        return substr($firstEntry, 0, $pos) . '/' . $assetsDirectory;
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DiscoverSliceAssets::class,
            ]);
        }
    }
}
