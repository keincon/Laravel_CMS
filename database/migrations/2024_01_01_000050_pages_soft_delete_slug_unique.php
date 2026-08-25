<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Partial unique: allow same slug after soft-delete.
            DB::statement('CREATE UNIQUE INDEX pages_slug_active_unique ON pages (slug) WHERE deleted_at IS NULL');
        } else {
            // SQLite/MySQL: enforce uniqueness in application validation (whereNull deleted_at).
            Schema::table('pages', function (Blueprint $table) {
                $table->index('slug');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS pages_slug_active_unique');
        } else {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropIndex(['slug']);
            });
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
