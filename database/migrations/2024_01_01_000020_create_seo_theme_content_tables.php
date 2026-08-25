<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('alt')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft'); // draft, publish, private, trash
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id', 'post_id']);
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['post_id', 'tag_id']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('featured_image_id')->nullable()->after('author_id')->constrained('media')->nullOnDelete();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_canonical')->nullable();
            $table->string('seo_robots')->nullable();
            $table->foreignId('seo_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_type')->nullable();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_canonical')->nullable();
            $table->string('seo_robots')->nullable();
            $table->foreignId('seo_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_type')->nullable();
        });

        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index, follow');
            $table->foreignId('default_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_type')->default('website');
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('og_site_name')->nullable();
            $table->string('og_locale')->default('en_US');
            $table->string('twitter_card')->default('summary_large_image');
            $table->boolean('sitemap_enabled')->default(true);
            $table->string('permalink_structure')->default('blog_slug'); // posts_slug, blog_slug, root_slug
            $table->string('organization_name')->nullable();
            $table->string('organization_logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme')->default('default');
            $table->string('primary_color', 20)->default('#2563EB');
            $table->string('secondary_color', 20)->default('#64748B');
            $table->string('accent_color', 20)->default('#7C3AED');
            $table->string('success_color', 20)->default('#16A34A');
            $table->string('warning_color', 20)->default('#D97706');
            $table->string('danger_color', 20)->default('#DC2626');
            $table->string('info_color', 20)->default('#0891B2');
            $table->string('background_color', 20)->default('#F8FAFC');
            $table->string('surface_color', 20)->default('#FFFFFF');
            $table->string('text_color', 20)->default('#0F172A');
            $table->string('color_mode')->default('system'); // light, dark, system
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('seo_settings');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seo_image_id');
            $table->dropConstrainedForeignId('og_image_id');
            $table->dropColumn([
                'seo_title', 'seo_description', 'seo_canonical', 'seo_robots',
                'og_title', 'og_description', 'og_type',
            ]);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_image_id');
            $table->dropConstrainedForeignId('seo_image_id');
            $table->dropConstrainedForeignId('og_image_id');
            $table->dropColumn([
                'seo_title', 'seo_description', 'seo_canonical', 'seo_robots',
                'og_title', 'og_description', 'og_type',
            ]);
        });

        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('media');
    }
};
