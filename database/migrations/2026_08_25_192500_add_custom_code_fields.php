<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'custom_css')) {
                $table->longText('custom_css')->nullable();
            }
            if (! Schema::hasColumn('posts', 'custom_js')) {
                $table->longText('custom_js')->nullable();
            }
            if (! Schema::hasColumn('posts', 'custom_html')) {
                $table->longText('custom_html')->nullable();
            }
        });

        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'custom_css')) {
                $table->longText('custom_css')->nullable();
            }
            if (! Schema::hasColumn('pages', 'custom_js')) {
                $table->longText('custom_js')->nullable();
            }
            if (! Schema::hasColumn('pages', 'custom_html')) {
                $table->longText('custom_html')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            foreach (['custom_css', 'custom_js', 'custom_html'] as $col) {
                if (Schema::hasColumn('posts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('pages', function (Blueprint $table) {
            foreach (['custom_css', 'custom_js', 'custom_html'] as $col) {
                if (Schema::hasColumn('pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
