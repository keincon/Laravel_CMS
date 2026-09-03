<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comments') || ! Schema::hasColumn('comments', 'post_id')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE comments MODIFY post_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE comments ALTER COLUMN post_id DROP NOT NULL');
        } else {
            // sqlite and others: recreate-friendly nullable via temporary table is heavy;
            // Schema::table without change() works on sqlite for nullable updates in Laravel.
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedBigInteger('post_id')->nullable()->change();
            });
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comments') || ! Schema::hasColumn('comments', 'post_id')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE comments MODIFY post_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE comments ALTER COLUMN post_id SET NOT NULL');
        } else {
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedBigInteger('post_id')->nullable(false)->change();
            });
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }
};
