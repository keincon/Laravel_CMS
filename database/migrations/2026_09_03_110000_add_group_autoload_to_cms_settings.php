<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_settings', 'group')) {
                $table->string('group')->default('general')->after('type');
            }
            if (! Schema::hasColumn('cms_settings', 'autoload')) {
                $table->boolean('autoload')->default(true)->after('group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('cms_settings', 'group')) {
                $columns[] = 'group';
            }
            if (Schema::hasColumn('cms_settings', 'autoload')) {
                $columns[] = 'autoload';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
