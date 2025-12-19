<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentMultisite\Models\Site;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'label')) {
                $table->string('label')->nullable()->after('name');
            }
        });

        Site::all()->each(function (Site $site) {
            $site->label = $site->name;
            $site->save();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'label')) {
                $table->dropColumn('label');
            }
        });
    }
};
