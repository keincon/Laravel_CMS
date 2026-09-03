<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LaravelPress Phase 1 — expand user profile fields (2FA-ready columns reserved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name')->nullable()->after('username');
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'nickname')) {
                $table->string('nickname')->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('nickname');
            }
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 16)->nullable()->after('bio');
            }
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('locale');
            }
            if (! Schema::hasColumn('users', 'avatar_media_id')) {
                $table->unsignedBigInteger('avatar_media_id')->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 32)->default('active')->after('avatar_media_id');
            }
            // Reserved for future 2FA — never store secrets in plaintext here.
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'display_name',
                'first_name',
                'last_name',
                'nickname',
                'bio',
                'locale',
                'timezone',
                'avatar_media_id',
                'status',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ];

            $existing = array_values(array_filter(
                $columns,
                static fn (string $column): bool => Schema::hasColumn('users', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
