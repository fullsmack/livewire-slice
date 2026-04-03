<?php
declare(strict_types=1);

namespace FullSmack\LivewireSlice;

use FullSmack\LaravelSlice\Slice;
use FullSmack\LaravelSlice\Extension;

class LivewireComponents implements Extension
{
    public function register(Slice $slice): void
    {
        app(\FullSmack\LivewireSlice\LivewireSliceRegistry::class)
            ->register($slice);
    }
}
