<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Zoker\FilamentMultisite\Services\AlternateLinks;

class AlternateLinksHead extends Component
{
    public Collection $items {
        get {
            return AlternateLinks::get();
        }
    }

    public function render(): View
    {
        return view('multisite::components.alternate-links-head');
    }
}
