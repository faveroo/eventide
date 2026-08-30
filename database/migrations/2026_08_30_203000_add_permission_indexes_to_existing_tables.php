<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasIndex('roles', 'roles_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique('slug');
            });
        }

        if (! Schema::hasIndex('roles', 'roles_name_guard_name_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasIndex('user_organization', 'user_organization_user_id_organization_id_unique')) {
            Schema::table('user_organization', function (Blueprint $table) {
                $table->unique(['user_id', 'organization_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('user_organization', 'user_organization_user_id_organization_id_unique')) {
            Schema::table('user_organization', function (Blueprint $table) {
                $table->dropUnique('user_organization_user_id_organization_id_unique');
            });
        }

        if (Schema::hasIndex('roles', 'roles_name_guard_name_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_name_guard_name_unique');
            });
        }

        if (Schema::hasIndex('roles', 'roles_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_slug_unique');
            });
        }
    }
};
