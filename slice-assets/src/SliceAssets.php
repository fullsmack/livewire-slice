<?php

declare(strict_types=1);

namespace FullSmack\SliceAssets;

use Illuminate\Filesystem\Filesystem;
use FullSmack\LaravelSlice\Slice;
use FullSmack\LaravelSlice\Extension;

/**
 * Laravel Slice extension that discovers frontend asset entry points for each slice.
 *
 * This mirrors the pattern from LivewireComponents: it implements the Extension
 * interface so that Laravel Slice calls register() for every registered slice.
 *
 * For each slice, it scans the configured assets directory for entry point files
 * (e.g. js/app.js, js/app.ts, css/app.css) and registers them in the AssetRegistry.
 *
 * The discovered entry points are then available for:
 * - The Blade directive to load slice-specific Vite assets
 * - The manifest command to export entry points for the Vite plugin
 */
class SliceAssets implements Extension
{
    public function __construct(
        private readonly AssetRegistry $registry,
        private readonly Filesystem $filesystem,
        private readonly string $assetsDirectory,
        private readonly array $entryPoints,
        private readonly string $basePath,
    ) {}

    /**
     * Called by Laravel Slice for each registered slice.
     *
     * Scans the slice's assets directory for configured entry point files
     * and registers any that exist.
     *
     * Example: For a slice at "slices/blog" with assets_directory "resources"
     * and entry_points ["js/app.js", "css/app.css"], this checks:
     *   - slices/blog/resources/js/app.js
     *   - slices/blog/resources/css/app.css
     * and registers whichever files actually exist.
     */
    public function register(Slice $slice): void
    {
        $sliceName = $slice->name();

        // The slice's root path (e.g. /project/slices/blog)
        $slicePath = $slice->path();

        // Build the full assets directory path
        $assetsDir = $slicePath . DIRECTORY_SEPARATOR . $this->assetsDirectory;

        if (! $this->filesystem->exists($assetsDir)) {
            return;
        }

        // Check each configured entry point and collect those that exist
        $discoveredEntryPoints = [];

        foreach ($this->entryPoints as $entryPoint) {
            $fullPath = $assetsDir . DIRECTORY_SEPARATOR . $entryPoint;

            if ($this->filesystem->exists($fullPath)) {
                $discoveredEntryPoints[] = $fullPath;
            }
        }

        if (empty($discoveredEntryPoints)) {
            return;
        }

        $this->registry->register($sliceName, $discoveredEntryPoints, $this->basePath);
    }
}
