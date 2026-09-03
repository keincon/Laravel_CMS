<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LaravelPress Phase 2 — generic content type / content / meta / revision tables.
 *
 * Existing posts/pages tables remain for backward compatibility. New features
 * should prefer the contents table; adapters can bridge during migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('singular_label');
            $table->string('plural_label');
            $table->json('supports')->nullable(); // title, editor, excerpt, author, ...
            $table->json('capabilities')->nullable();
            $table->boolean('hierarchical')->default(false);
            $table->boolean('has_archive')->default(true);
            $table->boolean('public')->default(true);
            $table->boolean('show_in_rest')->default(true);
            $table->string('rest_base')->nullable();
            $table->string('menu_icon')->nullable();
            $table->unsignedInteger('menu_position')->default(20);
            $table->boolean('is_builtin')->default(false);
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('content_type_id')->constrained('content_types')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable();
            $table->json('blocks')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('visibility', 32)->default('public');
            $table->string('password')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('comment_status', 16)->default('open');
            $table->string('template')->nullable();
            $table->unsignedInteger('menu_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['content_type_id', 'slug']);
            $table->index(['content_type_id', 'status']);
            $table->index(['author_id', 'status']);
        });

        Schema::create('content_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('key');
            $table->string('type', 32)->default('string');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'key']);
            $table->index('key');
        });

        // Enhance existing polymorphic revisions (payload/user_id) for LaravelPress.
        Schema::table('content_revisions', function (Blueprint $table) {
            if (! Schema::hasColumn('content_revisions', 'content_id')) {
                $table->foreignId('content_id')->nullable()->after('id')->constrained('contents')->nullOnDelete();
            }
            if (! Schema::hasColumn('content_revisions', 'revision_number')) {
                $table->unsignedInteger('revision_number')->default(1)->after('content_id');
            }
            if (! Schema::hasColumn('content_revisions', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('content_revisions', 'body')) {
                $table->longText('body')->nullable();
            }
            if (! Schema::hasColumn('content_revisions', 'excerpt')) {
                $table->text('excerpt')->nullable();
            }
            if (! Schema::hasColumn('content_revisions', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('content_revisions', 'blocks')) {
                $table->json('blocks')->nullable();
            }
        });

        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('singular_label');
            $table->string('plural_label');
            $table->boolean('hierarchical')->default(false);
            $table->boolean('public')->default(true);
            $table->boolean('show_in_rest')->default(true);
            $table->string('rest_base')->nullable();
            $table->json('content_types')->nullable(); // associated content type slugs
            $table->json('capabilities')->nullable();
            $table->boolean('is_builtin')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['taxonomy_id', 'slug']);
            $table->index(['taxonomy_id', 'parent_id']);
        });

        Schema::create('content_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['content_id', 'term_id']);
            $table->index('term_id');
        });

        Schema::create('term_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('key');
            $table->string('type', 32)->default('string');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['term_id', 'key']);
        });

        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key');
            $table->string('type', 32)->default('string');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        Schema::create('media_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('key');
            $table->string('type', 32)->default('string');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'key']);
        });

        Schema::create('media_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('variant'); // thumbnail, small, medium, large
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'variant']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->nullableMorphs('redirectable');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('media_variants');
        Schema::dropIfExists('media_meta');
        Schema::dropIfExists('user_meta');
        Schema::dropIfExists('term_meta');
        Schema::dropIfExists('content_term');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('taxonomies');

        if (Schema::hasTable('content_revisions') && Schema::hasColumn('content_revisions', 'content_id')) {
            Schema::table('content_revisions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('content_id');
                foreach (['revision_number', 'excerpt', 'metadata', 'blocks'] as $column) {
                    if (Schema::hasColumn('content_revisions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('content_meta');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('content_types');
    }
};
