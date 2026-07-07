<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sites', 'site_group_id')) {
            Schema::table('sites', function (Blueprint $table) {
                // Which group of alternates a site belongs to. No FK (matches
                // prefix/parent conventions); deleting a group leaves sites ungrouped.
                $table->unsignedBigInteger('site_group_id')->nullable()->after('id')->index();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        if (Schema::hasColumn('sites', 'site_group_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropIndex(['site_group_id']);
                $table->dropColumn('site_group_id');
            });
        }
    }

    /**
     * Preserve current behaviour: sites sharing a domain become one group (grouping
     * used to be domain-based). Each group is then guaranteed exactly one default.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('site_groups') || DB::table('sites')->whereNotNull('site_group_id')->exists()) {
            return;
        }

        foreach (DB::table('sites')->distinct()->pluck('domain') as $domain) {
            $scope = fn () => $domain === null
                ? DB::table('sites')->whereNull('domain')
                : DB::table('sites')->where('domain', $domain);

            $groupId = DB::table('site_groups')->insertGetId([
                'name' => $domain ?: 'Default',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $scope()->update(['site_group_id' => $groupId]);

            if (! $scope()->where('is_default', true)->exists()) {
                $defaultId = $scope()->where('is_active', true)->whereNull('prefix')->orderBy('id')->value('id')
                    ?? $scope()->where('is_active', true)->orderBy('id')->value('id');

                if ($defaultId) {
                    DB::table('sites')->where('id', $defaultId)->update(['is_default' => true]);
                }
            }
        }
    }
};
