<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use Livewire\Features\SupportConsoleCommands\Commands\ComponentParser;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Component parser that uses explicit slice paths instead of deriving from namespace.
 *
 * This class does NOT call the parent constructor to avoid the static generatePathFromNamespace
 * calls that would derive incorrect paths for custom slice namespaces.
 *
 * Future consideration for custom Livewire locations per slice:
 * - The Slice class could provide a `livewireConfig()` method returning custom namespace/path
 * - Or a slice-specific config could define 'livewire.namespace' and 'livewire.view-folder'
 */
class ComponentParserUsingCustomLocation extends ComponentParser
{
    protected string $sliceName;

    public function __construct(
        string $classNamespace,
        string $classPath,
        string $viewPath,
        string $testPath,
        string $testNamespace,
        string $rawComponentName,
        string $sliceName,
        ?string $stubSubDirectory = null,
    ) {
        // Do NOT call parent::__construct() - we set all properties explicitly

        $this->sliceName = $sliceName;

        $this->baseClassNamespace = $classNamespace;
        $this->baseTestNamespace = $testNamespace;

        $this->baseClassPath = rtrim($classPath, DIRECTORY_SEPARATOR) . '/';
        $this->baseViewPath = rtrim($viewPath, DIRECTORY_SEPARATOR) . '/';
        $this->baseTestPath = rtrim($testPath, DIRECTORY_SEPARATOR) . '/';

        if (!empty($stubSubDirectory) && Str::of($stubSubDirectory)->startsWith('..'))
        {
            $this->stubDirectory = rtrim((string) Str::of($stubSubDirectory)->replaceFirst('..' . DIRECTORY_SEPARATOR, ''), DIRECTORY_SEPARATOR) . '/';
        }
        else
        {
            $this->stubDirectory = rtrim('stubs' . DIRECTORY_SEPARATOR . ($stubSubDirectory ?? ''), DIRECTORY_SEPARATOR) . '/';
        }

        $directories = preg_split('/[.\/(\\\\)]+/', $rawComponentName);

        $camelCase = Str::of(array_pop($directories))->camel();
        $kebabCase = Str::of($camelCase)->kebab();

        $this->component = $kebabCase;
        $this->componentClass = Str::of($this->component)->studly();

        $this->directories = array_map(Str::studly(...), $directories);
    }

    public function viewName()
    {
        $viewFolder = config('livewire-slice.view-folder');

        return $this->sliceName . '::' . $viewFolder . '.' . Collection::make($this->directories)
            ->map(Str::kebab(...))
            ->push($this->component)
            ->implode('.');
    }
}
