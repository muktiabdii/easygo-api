<?php

// database/migrations/2025_06_01_164900_add_reviews_count_and_last_active_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewsCountAndLastActiveToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'reviews_count')) {
                $table->unsignedInteger('reviews_count')->default(0)->after('email');
            }
            if (!Schema::hasColumn('users', 'last_active')) {
                $table->timestamp('last_active')->nullable()->after('reviews_count');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reviews_count', 'last_active']);
        });
    }
}
