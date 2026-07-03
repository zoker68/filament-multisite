<?php

namespace Zoker\FilamentMultisite\Observers;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;

class SiteObserver
{
    public function created(Site $site): void
    {
        $this->cacheClear($site);
    }

    public function saving(Site $site): void
    {
        // An EXISTING default cannot be unset directly: the default is changed by
        // flagging another site (which clears this one via enforceSingleDefault),
        // not by turning the only one off. Creating a non-default site is fine —
        // this only guards updates, so a site can still be created with no default.
        if ($site->exists && $site->isDirty('is_default') && ! $site->is_default && ! $this->anotherDefaultExists($site)) {
            $site->is_default = true;
        }
    }

    public function saved(Site $site): void
    {
        $this->enforceSingleDefault($site);
        $this->cacheClear($site);
    }

    /**
     * Exactly one site is the default: setting one clears the flag on the rest.
     * Uses a query-builder update so it does not re-fire this observer.
     */
    private function enforceSingleDefault(Site $site): void
    {
        if (! $site->is_default) {
            return;
        }

        Site::query()
            ->where('id', '!=', $site->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function anotherDefaultExists(Site $site): bool
    {
        return Site::query()
            ->where('id', '!=', $site->id ?? 0)
            ->where('is_default', true)
            ->exists();
    }

    public function deleting(Site $site): void
    {
        // Deleting the default reassigns it to another active site (an unprefixed
        // one first) so a default always exists.
        if (! $site->is_default) {
            return;
        }

        Site::query()
            ->where('id', '!=', $site->id)
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN prefix IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first()
            ?->update(['is_default' => true]);
    }

    public function deleted(Site $site): void
    {
        $this->cacheClear($site);
    }

    private function cacheClear(Site $site): void
    {
        cache()->forget(FilamentMultisiteRouteServiceProvider::MULTISITE_AVAILABLE_PREFIXES_CACHE_KEY);
        cache()->forget(FilamentMultisiteRouteServiceProvider::TRANSLATABLE_LOCALES_CACHE_KEY);

        $site->clearCache();
    }
}
