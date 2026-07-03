<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sites', 'is_default')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_active');
            });
        }

        // Preserve existing behaviour: flag the site the old getCurrentSite() fallback
        // resolved to — an active, unprefixed site on the app.url host (or with no
        // domain), else any active unprefixed site, else the first active site.
        if (! DB::table('sites')->where('is_default', true)->exists()) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            $default = DB::table('sites')->where('is_active', true)->whereNull('prefix')
                ->where(function ($query) use ($appHost) {
                    $query->whereNull('domain');
                    if ($appHost) {
                        $query->orWhere('domain', $appHost);
                    }
                })
                ->orderBy('id')->first()
                ?? DB::table('sites')->where('is_active', true)->whereNull('prefix')->orderBy('id')->first()
                ?? DB::table('sites')->where('is_active', true)->orderBy('id')->first();

            if ($default) {
                DB::table('sites')->where('id', $default->id)->update(['is_default' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sites', 'is_default')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
