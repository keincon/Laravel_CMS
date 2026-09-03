<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'is_sticky')) {
                $table->boolean('is_sticky')->default(false)->after('status');
                $table->index('is_sticky');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'is_sticky')) {
                $table->dropIndex(['is_sticky']);
                $table->dropColumn('is_sticky');
            }
        });
    }
};
