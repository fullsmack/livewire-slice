# Slice Assets — Proof of Concept

> **PoC branch — not intended for merge into livewire-slice.**

This is a proof of concept for a Laravel package + Vite plugin that lets you
co-locate frontend assets (JS, TS, CSS, Vue, React, etc.) inside Laravel Slice
modules instead of the main `resources/` directory.

The same way `livewire-slice` registers Livewire PHP components from slices,
**slice-assets** registers frontend entry points from slices so that Vite
bundles them alongside the main application assets.

---

## Problem

In a modular Laravel application using [laravel-slice](https://github.com/fullsmack/laravel-slice),
backend code lives inside slice directories:

```
slices/
  blog/
    src/
    routes/
    resources/views/
  admin/
    src/
    routes/
    resources/views/
```

But frontend assets still live in the monolithic `resources/js` and
`resources/css` at the project root. This breaks the modularity — a slice's
JavaScript/CSS is separated from the rest of its code.

## Solution

**slice-assets** has two cooperating halves:

| Side | Purpose |
|------|---------|
| **PHP package** (`src/`) | Discovers entry points in each slice, provides a Blade directive to load them, and generates a manifest JSON |
| **Vite plugin** (`vite/`) | Reads the manifest (or scans directories) and adds slice entry points to Vite's build config |

After setup, each slice owns its frontend:

```
slices/
  blog/
    src/
    resources/
      js/app.js          ← slice entry point (JS)
      css/app.css         ← slice entry point (CSS)
      js/components/      ← slice-local components
    routes/
  admin/
    src/
    resources/
      js/app.ts           ← TypeScript is fine too
```

---

## Architecture

### How it works — step by step

```
┌──────────────────────────────────────────────────────────────┐
│                     BOOT TIME (PHP)                          │
│                                                              │
│  1. Laravel Slice calls SliceAssets::register($slice)        │
│     for every registered slice                               │
│                                                              │
│  2. SliceAssets checks if the slice has an assets directory   │
│     (e.g. slices/blog/resources/) with known entry points    │
│     (e.g. js/app.js, css/app.css)                            │
│                                                              │
│  3. Discovered entry points are added to the AssetRegistry   │
│     singleton                                                │
└──────────────────┬───────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────┐
│              ARTISAN COMMAND (PHP → JSON)                     │
│                                                              │
│  php artisan slice-assets:discover                           │
│                                                              │
│  Writes bootstrap/cache/slice-assets.json:                   │
│  {                                                           │
│    "slices": {                                               │
│      "blog": ["slices/blog/resources/js/app.js",             │
│               "slices/blog/resources/css/app.css"],           │
│      "admin": ["slices/admin/resources/js/app.ts"]           │
│    },                                                        │
│    "entry_points": [                                         │
│      "slices/blog/resources/js/app.js",                      │
│      "slices/blog/resources/css/app.css",                    │
│      "slices/admin/resources/js/app.ts"                      │
│    ]                                                         │
│  }                                                           │
└──────────────────┬───────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────┐
│                  BUILD TIME (Vite / Node)                     │
│                                                              │
│  The Vite plugin reads the manifest and:                     │
│                                                              │
│  1. Adds all slice entry points to rollup's input config     │
│     so they are included in the production build             │
│                                                              │
│  2. Creates import aliases for each slice:                   │
│     @slice:blog → slices/blog/resources/                     │
│     @slice:admin → slices/admin/resources/                   │
│                                                              │
│  3. In dev mode, watches slice directories for HMR           │
└──────────────────────────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────┐
│                  RENDER TIME (Blade)                          │
│                                                              │
│  @sliceVite('blog')                                          │
│                                                              │
│  Generates <script> and <link> tags for the blog slice's     │
│  bundled assets, using Laravel's Vite facade so HMR works    │
│  during development and hashed URLs in production.           │
└──────────────────────────────────────────────────────────────┘
```

### Component overview

| File | Role |
|------|------|
| `src/SliceAssets.php` | **Extension** — implements `FullSmack\LaravelSlice\Extension`, called once per slice to discover asset entry points |
| `src/AssetRegistry.php` | **Registry** — singleton that stores all discovered entry points, used by the Blade directive and manifest command |
| `src/SliceAssetsServiceProvider.php` | **Service provider** — registers config, the `@sliceVite` Blade directive, and the artisan command |
| `src/Command/DiscoverSliceAssets.php` | **Artisan command** — writes `slice-assets.json` manifest for the Vite plugin |
| `config/slice-assets.php` | **Configuration** — assets directory name, entry point file names, manifest path |
| `vite/src/index.js` | **Vite plugin** — reads manifest or scans directories, adds entry points to Vite build config |
| `vite/src/index.d.ts` | TypeScript declarations for the Vite plugin |

---

## Installation (hypothetical)

### PHP package

```bash
composer require fullsmack/slice-assets
```

Publish config (optional):

```bash
php artisan vendor:publish --provider="FullSmack\SliceAssets\SliceAssetsServiceProvider" --tag=config
```

### Vite plugin

```bash
npm install vite-plugin-slice-assets --save-dev
```

### Register the Extension with Laravel Slice

In your `config/laravel-slice.php`:

```php
'extensions' => [
    \FullSmack\SliceAssets\SliceAssets::class,
],
```

This tells Laravel Slice to call `SliceAssets::register()` for every slice,
the same way `LivewireComponents` is registered.

---

## Usage

### 1. Add frontend assets to your slice

```
slices/
  blog/
    resources/
      js/
        app.js              ← entry point
        components/
          PostEditor.vue    ← slice-local component
      css/
        app.css             ← entry point
```

### 2. Generate the manifest

```bash
php artisan slice-assets:discover
```

Output:

```
Discovered 3 entry point(s) across 2 slice(s).
  blog:
    - slices/blog/resources/js/app.js
    - slices/blog/resources/css/app.css
  admin:
    - slices/admin/resources/js/app.ts

Manifest written to: /app/bootstrap/cache/slice-assets.json
```

### 3. Configure Vite

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import sliceAssets from 'vite-plugin-slice-assets';

export default defineConfig({
    plugins: [
        // Main app assets (unchanged)
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
        }),

        // Slice assets (add this)
        sliceAssets({
            // Option A: Read from manifest (recommended)
            manifestPath: 'bootstrap/cache/slice-assets.json',

            // Option B: Scan directories directly (no manifest needed)
            // slicesDirectory: 'slices',
        }),
    ],
});
```

### 4. Load slice assets in Blade templates

```blade
{{-- In your slice's layout or view --}}

{{-- Load all entry points for the blog slice --}}
@sliceVite('blog')

{{-- Or load a specific entry point --}}
@sliceVite('admin', 'js/admin-dashboard.js')
```

### 5. Import across slices

The Vite plugin creates aliases so you can import from other slices cleanly:

```js
// Inside slices/admin/resources/js/app.js
import { formatPost } from '@slice:blog/js/utils/format.js';
```

---

## Configuration

### PHP config (`config/slice-assets.php`)

```php
return [
    // Directory within each slice that holds frontend assets
    'assets_directory' => 'resources',

    // Entry point files to look for (relative to assets_directory)
    'entry_points' => [
        'js/app.js',
        'js/app.ts',
        'css/app.css',
    ],

    // Where to write the manifest file
    'manifest_path' => 'bootstrap/cache/slice-assets.json',

    // Vite build output directory (should match your laravel-vite-plugin config)
    'build_directory' => 'build',
];
```

### Vite plugin options

| Option | Default | Description |
|--------|---------|-------------|
| `manifestPath` | `'bootstrap/cache/slice-assets.json'` | Path to the PHP-generated manifest |
| `slicesDirectory` | `null` | Set to scan directories instead of reading manifest (e.g. `'slices'`) |
| `entryPoints` | `['resources/js/app.js', ...]` | Entry points to look for per slice (directory scan mode only) |
| `assetsDirectory` | `'resources'` | Assets directory name within each slice |
| `aliasPrefix` | `'@slice:'` | Prefix for import aliases |

---

## Directory Scan vs Manifest

The Vite plugin supports two discovery modes:

### Manifest mode (recommended for production)

```js
sliceAssets({
    manifestPath: 'bootstrap/cache/slice-assets.json',
})
```

- Requires running `php artisan slice-assets:discover` beforehand
- Most reliable: uses the same PHP-side slice registration as your app
- Add `php artisan slice-assets:discover` to your CI/CD pipeline before `npm run build`

### Directory scan mode (convenient for development)

```js
sliceAssets({
    slicesDirectory: 'slices',
    entryPoints: ['resources/js/app.js', 'resources/css/app.css'],
})
```

- No manifest needed — scans the filesystem directly
- Supports nested slices: `slices/api/posts/` → slice name `api.posts`
- May discover files that aren't registered as slices in PHP

---

## How it compares to livewire-slice

| Aspect | livewire-slice | slice-assets |
|--------|---------------|--------------|
| **Registers** | PHP Livewire components | Frontend asset entry points |
| **Extension method** | Scans for `Component` subclasses | Checks for entry point files |
| **Integration** | Livewire component registry | Vite build config + Blade directive |
| **Blade usage** | `<livewire:blog::post-list />` | `@sliceVite('blog')` |
| **Runtime** | PHP only | PHP + Node (Vite plugin) |

Both implement `FullSmack\LaravelSlice\Extension` and follow the same pattern:
discover things in slices at boot time, register them with the appropriate system.

---

## Limitations of this PoC

- The Blade directive uses a simplified approach to Vite integration; a production
  version would need tighter integration with `laravel-vite-plugin`'s manifest
  handling and hot-file detection.
- The Vite plugin merges entry points into `build.rollupOptions.input`, which
  works but may need coordination with `laravel-vite-plugin`'s own input handling
  depending on how they compose.
- No test coverage (intentional for PoC).
- The `SliceAssets` extension constructor requires explicit dependency injection;
  when registered as an extension in Laravel Slice config, the DI container needs
  to resolve it with the right config values (see example binding below).

### Example container binding

If Laravel Slice resolves extensions from the container, you'd bind it like:

```php
// In a service provider
$this->app->bind(\FullSmack\SliceAssets\SliceAssets::class, function ($app) {
    return new \FullSmack\SliceAssets\SliceAssets(
        registry: $app->make(\FullSmack\SliceAssets\AssetRegistry::class),
        filesystem: $app->make(\Illuminate\Filesystem\Filesystem::class),
        assetsDirectory: config('slice-assets.assets_directory', 'resources'),
        entryPoints: config('slice-assets.entry_points', []),
        basePath: base_path(),
    );
});
```
