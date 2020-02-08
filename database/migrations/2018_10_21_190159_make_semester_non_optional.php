<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeSemesterNonOptional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_last_echo_foreign');
        });

        // Due to a bug in Laravel 5.7, foreign key constraints operations are always executed last.
        // Therefore, whenever we want to operate on a foreign key, we need to close the anonymous function directly after the operation.

        Schema::table('users', function (Blueprint $table) {
            $table->integer('last_echo')->unsigned()->nullable(false)->change();
            $table->foreign('last_echo')->references('id')->on('semesters')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_last_echo_foreign');
        });

        // Due to a bug in Laravel 5.7, foreign key constraints operations are always executed last.
        // Therefore, whenever we want to operate on a foreign key, we need to close the anonymous function directly after the operation.

        Schema::table('users', function (Blueprint $table) {
            $table->integer('last_echo')->unsigned()->nullable(true)->change();
            $table->foreign('last_echo')->references('id')->on('semesters')->onDelete('set null');
        });
    }
}
