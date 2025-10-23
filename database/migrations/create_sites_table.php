<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentMultisite\Models\Site;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('prefix')->nullable();
            $table->string('locale')->default(app()->getLocale());
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Site::create([
            'code' => 'default',
            'name' => 'Default',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
