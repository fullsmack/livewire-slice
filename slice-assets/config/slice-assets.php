<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Assets Directory
    |--------------------------------------------------------------------------
    |
    | The directory within each slice that contains frontend assets.
    | Relative to the slice root path.
    |
    | For a slice at "slices/blog", assets would be at "slices/blog/resources".
    |
    */
    'assets_directory' => 'resources',

    /*
    |--------------------------------------------------------------------------
    | Entry Points
    |--------------------------------------------------------------------------
    |
    | The entry point file paths to look for within each slice's assets
    | directory. These are the files that Vite will use as build entry points.
    |
    | Each entry point is relative to the slice's assets_directory.
    | The first matching file per group wins (so you can list both .js and .ts
    | alternatives and only the one that exists will be registered).
    |
    */
    'entry_points' => [
        'js/app.js',
        'js/app.ts',
        'css/app.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Manifest Path
    |--------------------------------------------------------------------------
    |
    | Where the discovered slice assets manifest is written. This JSON file
    | is read by the Vite plugin to know which slice entry points to include
    | in the build.
    |
    | Path is relative to the Laravel project root.
    |
    */
    'manifest_path' => 'bootstrap/cache/slice-assets.json',

    /*
    |--------------------------------------------------------------------------
    | Vite Build Directory
    |--------------------------------------------------------------------------
    |
    | The build output directory used by Vite, relative to the public path.
    | This should match the `buildDirectory` option in your laravel-vite-plugin
    | configuration.
    |
    */
    'build_directory' => 'build',

];
