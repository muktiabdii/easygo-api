<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('reset_otp')->nullable(); // Menambahkan kolom reset_otp
        $table->timestamp('otp_expires_at')->nullable(); // Menambahkan kolom otp_expires_at
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('reset_otp');
        $table->dropColumn('otp_expires_at');
    });
}

};
