<?php

declare(strict_types=1);

namespace FullSmack\SliceAssets\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use FullSmack\SliceAssets\AssetRegistry;

/**
 * Artisan command that scans all registered slices for frontend assets
 * and writes a manifest JSON file.
 *
 * The manifest is consumed by the Vite plugin (vite-plugin-slice-assets)
 * so that it knows which entry points to include in the build, without
 * needing to replicate the PHP-side slice discovery logic in JavaScript.
 *
 * Usage:
 *   php artisan slice-assets:discover
 *
 * The manifest is written to the path configured in slice-assets.manifest_path
 * (default: bootstrap/cache/slice-assets.json).
 *
 * You should run this command:
 *   - After adding a new slice with frontend assets
 *   - After changing entry point file names
 *   - In your CI/CD pipeline before running `npm run build`
 */
class DiscoverSliceAssets extends Command
{
    protected $signature = 'slice-assets:discover';

    protected $description = 'Discover frontend asset entry points from all slices and write a manifest';

    public function handle(AssetRegistry $registry, Filesystem $filesystem): int
    {
        $manifestPath = base_path(
            config('slice-assets.manifest_path', 'bootstrap/cache/slice-assets.json')
        );

        $manifest = $registry->toManifest();

        // Ensure the directory exists
        $directory = dirname($manifestPath);

        if (! $filesystem->exists($directory)) {
            $filesystem->makeDirectory($directory, 0755, true);
        }

        $filesystem->put(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $sliceCount = count($manifest['slices']);
        $entryPointCount = count($manifest['entry_points']);

        $this->info("Discovered {$entryPointCount} entry point(s) across {$sliceCount} slice(s).");

        foreach ($manifest['slices'] as $sliceName => $entryPoints) {
            $this->line("  <comment>{$sliceName}</comment>:");

            foreach ($entryPoints as $entryPoint) {
                $this->line("    - {$entryPoint}");
            }
        }

        $this->newLine();
        $this->info("Manifest written to: {$manifestPath}");

        return self::SUCCESS;
    }
}
