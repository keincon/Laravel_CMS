<?php

use App\Models\ThemeSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('theme_settings') && Schema::hasColumn('theme_settings', 'color_mode')) {
            // Fresh default for new rows.
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE theme_settings ALTER COLUMN color_mode SET DEFAULT 'light'");
            }

            // Existing installs that still use system → light site default.
            ThemeSetting::query()
                ->where(function ($q) {
                    $q->where('color_mode', 'system')->orWhereNull('color_mode');
                })
                ->update(['color_mode' => 'light']);

            ThemeSetting::forgetCache();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('theme_settings') && Schema::hasColumn('theme_settings', 'color_mode')) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE theme_settings ALTER COLUMN color_mode SET DEFAULT 'system'");
            }
        }
    }
};
