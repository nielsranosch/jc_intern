<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserPrivacySetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_always_see_private_data')->after('musical_leadership')->default(false);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('share_private_data')->after('voice_id')->default(false);
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
            $table->dropColumn('share_private_data');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_always_see_private_data');
        });
    }
}
