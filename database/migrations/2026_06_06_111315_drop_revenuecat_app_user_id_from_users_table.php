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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'revenuecat_app_user_id')) {
                $table->dropUnique(['revenuecat_app_user_id']);
                $table->dropColumn('revenuecat_app_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'revenuecat_app_user_id')) {
                $table->string('revenuecat_app_user_id')->nullable()->unique();
            }
        });
    }
};
