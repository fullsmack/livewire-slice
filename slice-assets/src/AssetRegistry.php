<?php

declare(strict_types=1);

namespace FullSmack\SliceAssets;

use Illuminate\Support\Collection;

/**
 * Singleton registry that collects all discovered slice asset entry points.
 *
 * As each slice is registered via the SliceAssets extension, its frontend
 * entry points are added here. The registry is then used by:
 *
 * - The Blade directive (@sliceVite) to resolve asset paths for a given slice
 * - The artisan command to generate a manifest JSON for the Vite plugin
 */
class AssetRegistry
{
    /**
     * Map of slice name => array of entry point paths (relative to project root).
     *
     * Example:
     * [
     *     'blog' => [
     *         'slices/blog/resources/js/app.js',
     *         'slices/blog/resources/css/app.css',
     *     ],
     *     'admin' => [
     *         'slices/admin/resources/js/app.ts',
     *     ],
     * ]
     *
     * @var array<string, list<string>>
     */
    private array $slices = [];

    /**
     * Register entry points for a slice.
     *
     * @param string $sliceName  The slice identifier (e.g. "blog", "api.posts")
     * @param list<string> $entryPoints  Absolute paths to discovered entry point files
     * @param string $basePath  The Laravel project base path, used to make paths relative
     */
    public function register(string $sliceName, array $entryPoints, string $basePath): void
    {
        // Store paths relative to the project root, which is what Vite expects
        $this->slices[$sliceName] = array_map(
            static fn (string $absolutePath): string => ltrim(
                str_replace($basePath, '', $absolutePath),
                DIRECTORY_SEPARATOR
            ),
            $entryPoints
        );
    }

    /**
     * Get all entry points for a specific slice.
     *
     * @return list<string> Entry point paths relative to project root
     */
    public function getEntryPoints(string $sliceName): array
    {
        return $this->slices[$sliceName] ?? [];
    }

    /**
     * Get all registered slices and their entry points.
     *
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->slices;
    }

    /**
     * Get a flat list of every entry point across all slices.
     *
     * @return list<string>
     */
    public function allEntryPoints(): array
    {
        return Collection::make($this->slices)
            ->flatten()
            ->values()
            ->all();
    }

    /**
     * Check if a slice has any registered entry points.
     */
    public function has(string $sliceName): bool
    {
        return isset($this->slices[$sliceName]) && count($this->slices[$sliceName]) > 0;
    }

    /**
     * Export the registry as a manifest array suitable for JSON serialization.
     *
     * The manifest is consumed by the Vite plugin to add slice entry points
     * to the build configuration.
     *
     * @return array{slices: array<string, list<string>>, entry_points: list<string>}
     */
    public function toManifest(): array
    {
        return [
            'slices' => $this->slices,
            'entry_points' => $this->allEntryPoints(),
        ];
    }
}
