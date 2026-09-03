<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidebar_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // search, recent_posts, categories, text, custom_html
            $table->string('title')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $sidebarId = DB::table('sidebars')->insertGetId([
            'name' => 'Main Sidebar',
            'slug' => 'main',
            'description' => 'Default blog sidebar',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $widgets = [
            ['type' => 'search', 'title' => 'Search', 'settings' => json_encode([]), 'sort_order' => 1],
            ['type' => 'categories', 'title' => 'Categories', 'settings' => json_encode(['limit' => 12]), 'sort_order' => 2],
            ['type' => 'recent_posts', 'title' => 'Recent Posts', 'settings' => json_encode(['limit' => 5]), 'sort_order' => 3],
        ];

        foreach ($widgets as $widget) {
            DB::table('widgets')->insert([
                'sidebar_id' => $sidebarId,
                'type' => $widget['type'],
                'title' => $widget['title'],
                'settings' => $widget['settings'],
                'sort_order' => $widget['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('sidebars');
    }
};
