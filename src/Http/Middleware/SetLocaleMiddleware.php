<?php

namespace Zoker\FilamentMultisite\Http\Middleware;

use App;
use Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Zoker\FilamentMultisite\Models\Site;

class SetLocaleMiddleware
{
    public function handle($request, \Closure $next)
    {
        $host = $this->getHost();
        $prefix = request()->segment(1);

        $sitesForHost = Site::query()
            ->where('domain', $host)->orWhereNull('domain')
            ->where(function (Builder $query) use ($prefix) {
                $query->where('prefix', $prefix)->orWhereNull('prefix');
            })
            ->get();

        $sitesForHost = $sitesForHost->where('domain', $host) ?? $sitesForHost->whereNull('domain');

        $activeSite = $sitesForHost->firstWhere('prefix', $prefix) ?? $sitesForHost->firstWhere('prefix', null);

        if (! $activeSite || ! $activeSite->is_active) {
            abort(404, 'Site not found');
        }

        app()->instance('currentSite', $activeSite);

        App::setLocale($activeSite->locale);
        Config::set('app.locale', $activeSite->locale);

        if ($activeSite->prefix) {
            URL::defaults(['multisite_prefix' => $activeSite->prefix]);
        }

        $request->route()->forgetParameter('multisite_prefix');

        return $next($request);
    }

    private function getHost(): ?string
    {
        $defaultHost = parse_url(config('app.url'))['host'];

        if ($defaultHost == request()->getHost()) {
            return null;
        }

        return request()->getHost();
    }
}
