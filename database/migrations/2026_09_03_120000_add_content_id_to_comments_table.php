<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow comments to target LaravelPress contents (dual-write era keeps post_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (! Schema::hasColumn('comments', 'content_id')) {
                $table->foreignId('content_id')->nullable()->after('post_id')->constrained('contents')->nullOnDelete();
                $table->index('content_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'content_id')) {
                $table->dropConstrainedForeignId('content_id');
            }
        });
    }
};
