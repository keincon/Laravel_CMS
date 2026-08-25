<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
            $table->text('excerpt')->nullable()->after('content');
            $table->softDeletes();
        });

        Schema::create('dynamic_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('label');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('url_path')->nullable();
            $table->unsignedInteger('posts_per_page')->default(12);
            $table->string('layout')->default('list');
            $table->string('sidebar_position')->nullable();
            $table->string('template')->default('default');
            $table->string('header_mode')->default('master');
            $table->foreignId('header_id')->nullable()->constrained('headers')->nullOnDelete();
            $table->string('footer_mode')->default('master');
            $table->foreignId('footer_id')->nullable()->constrained('footers')->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('seo_title_template')->nullable();
            $table->text('seo_description_template')->nullable();
            $table->string('seo_robots')->nullable();
            $table->string('message')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->string('image_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('seo_settings', function (Blueprint $table) {
            $table->json('seo_templates')->nullable()->after('permalink_structure');
        });
    }

    public function down(): void
    {
        Schema::table('seo_settings', function (Blueprint $table) {
            $table->dropColumn('seo_templates');
        });

        Schema::dropIfExists('dynamic_page_settings');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('excerpt');
        });
    }
};
