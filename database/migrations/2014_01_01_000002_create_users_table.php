<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email', 191)->unique();  // InnoDB (MySQL's engine) can handle VARCHARs only up to 191 when UNIQUE is selected.
            $table->string('password');
            $table->date('birthday')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_street')->nullable();
            $table->unsignedInteger('address_zip')->nullable();
            $table->string('address_city')->nullable();
            $table->boolean('sheets_deposit_returned')->default(false);

            // Can only be in one voice
            $table->integer('voice_id')->unsigned();
            $table->foreign('voice_id')->references('id')->on('voices')->onDelete('no action');

            // Letzte Semester-Rueckmeldung.
            $table->integer('last_echo')->unsigned()->nullable()->default(null);
            $table->foreign('last_echo')->references('id')->on('semesters')->onDelete('set null');

            // Pseudo-ID and -password for calendar synchronization
            $table->string('pseudo_id', 191)->unique(); // InnoDB (MySQL's engine) can handle VARCHARs only up to 191 when UNIQUE is selected.
            $table->string('pseudo_password');
            /*
             * The Pseudo ID will be passed by calendar-sync-clients to identify the user.
             * This dedicated field should be filled with a random string to reveal as little information about our system as possible.
             * The Pseudo Password is a 'reverse' password. That means, the server stores the clear text, whereas the client only ever gets to see the hash.
             */

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
