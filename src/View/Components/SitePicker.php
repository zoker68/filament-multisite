<?php

declare(strict_types=1);

namespace Zoker\FilamentMultisite\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\AlternateLinks;

class SitePicker extends Component
{
    public Collection $items;

    public function render(): View
    {
        $this->items = AlternateLinks::get()->keyBy('site.id');

        $sites = Site::getForDomain(SiteManager::getCurrentSite()->domain)
            ->map(fn (Site $site) => [
                'site' => $site,
                'url' => $this->items[$site->id]['url'] ?? $site->url,
                'isActive' => $site->id === SiteManager::getCurrentSiteId(),
            ]);

        return view('multisite::components.site-picker', compact('sites'));
    }
}
