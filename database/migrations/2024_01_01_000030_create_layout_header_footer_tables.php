<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('content')->nullable(); // published structure
            $table->json('draft_content')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('footers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('content')->nullable();
            $table->json('draft_content')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('layout_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('default_header_id')->nullable()->constrained('headers')->nullOnDelete();
            $table->foreignId('default_footer_id')->nullable()->constrained('footers')->nullOnDelete();
            $table->unsignedInteger('container_width')->default(1200);
            $table->unsignedInteger('content_width')->default(900);
            $table->unsignedInteger('sidebar_width')->default(300);
            $table->string('page_layout')->default('standard'); // standard, full_width
            $table->string('post_layout')->default('standard');
            $table->string('sidebar_position')->default('right'); // none, left, right
            $table->string('homepage_type')->default('static'); // static, posts
            $table->foreignId('homepage_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('posts_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('html'); // hero, text, cta, html, etc.
            $table->json('content')->nullable();
            $table->boolean('is_global')->default(false);
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('template')->default('default')->after('status');
            $table->string('header_mode')->default('master')->after('template'); // master, custom, disable
            $table->foreignId('header_id')->nullable()->after('header_mode')->constrained('headers')->nullOnDelete();
            $table->string('footer_mode')->default('master')->after('header_id');
            $table->foreignId('footer_id')->nullable()->after('footer_mode')->constrained('footers')->nullOnDelete();
            $table->string('sidebar_position')->nullable()->after('footer_id'); // null = inherit
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('template')->default('default')->after('status');
            $table->string('header_mode')->default('master')->after('template');
            $table->foreignId('header_id')->nullable()->after('header_mode')->constrained('headers')->nullOnDelete();
            $table->string('footer_mode')->default('master')->after('header_id');
            $table->foreignId('footer_id')->nullable()->after('footer_mode')->constrained('footers')->nullOnDelete();
            $table->string('sidebar_position')->nullable()->after('footer_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('header_id');
            $table->dropConstrainedForeignId('footer_id');
            $table->dropColumn(['template', 'header_mode', 'footer_mode', 'sidebar_position']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('header_id');
            $table->dropConstrainedForeignId('footer_id');
            $table->dropColumn(['template', 'header_mode', 'footer_mode', 'sidebar_position']);
        });

        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('layout_settings');
        Schema::dropIfExists('footers');
        Schema::dropIfExists('headers');
    }
};
