<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'id' => 1,
            'first_name' => 'Admin',
            'last_name' => 'Account',
            'email' => 'test@gmail.com',
            'password' => bcrypt('secret'),
            'voice_id' => 1,
            'last_echo' => 1,
            'pseudo_id' => Str::random(20),
            'pseudo_password' => Str::random(222),
        ]);

        /*DB::table('users')->insert([
            'id' => 2,
            'first_name' => 'Stimm',
            'last_name' => 'Führer',
            'email' => 'test1@gmail.com',
            'password' => bcrypt('secret'),
            'voice_id' => 6,
            'last_echo' => 1,
        ]);

        DB::table('users')->insert([
            'id' => 3,
            'first_name' => 'Jan Hendrik',
            'last_name' => 'Hermann',
            'email' => 'test2@gmail.com',
            'password' => bcrypt('secret'),
            'voice_id' => 1,
            'last_echo' => 1,
        ]);*/

        factory(App\Models\User::class, 20)->create();
    }
}
