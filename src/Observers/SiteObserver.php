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
     * Exactly one default PER GROUP: flagging one clears the flag on the others in
     * its group. Uses a query-builder update so it does not re-fire this observer.
     */
    private function enforceSingleDefault(Site $site): void
    {
        if (! $site->is_default) {
            return;
        }

        Site::query()
            ->inGroup($site->site_group_id)
            ->where('id', '!=', $site->id)
            ->default()
            ->update(['is_default' => false]);

        // The mass update fires no observers; clear this group's cache (no TTL) so the
        // demoted default's stale cached is_default doesn't drive a wrong hreflang x-default.
        cache()->forget(Site::SITES_FOR_GROUP_CACHE_KEY . ($site->site_group_id ?? 'null'));
    }

    private function anotherDefaultExists(Site $site): bool
    {
        return Site::query()
            ->inGroup($site->site_group_id)
            ->where('id', '!=', $site->id ?? 0)
            ->default()
            ->exists();
    }

    public function deleting(Site $site): void
    {
        // Deleting a group's default reassigns it to another active site in the SAME
        // group (an unprefixed one first) so each group always has a default.
        if (! $site->is_default) {
            return;
        }

        Site::query()
            ->inGroup($site->site_group_id)
            ->where('id', '!=', $site->id)
            ->active()
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
