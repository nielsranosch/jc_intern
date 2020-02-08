<?php

use Illuminate\Database\Seeder;

class DatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        // There is currently no reliable way to create consistent data.

        /* DB::table('rehearsals')->insert([
            'semester_id' => 1,
            'title'       => 'Erste Probe',
            'start'       => '2018-04-01 19:30:00',
            'end'         => '2018-04-01 21:45:00',
            'place'       => 'Hörsaal X',
            'binary_answer'   => true,
            'mandatory'   => false,
            'weight'      => 1.0,
        ]);

        DB::table('rehearsals')->insert([
            'semester_id' => 1,
            'title'       => 'Schnupper-Probe',
            'start'       => '2018-04-11 19:30:00',
            'end'         => '2018-04-11 21:45:00',
            'place'       => 'Hörsaal X',
            'binary_answer'   => true,
            'mandatory'   => true,
            'weight'      => 1.0,
        ]);

        DB::table('rehearsals')->insert([
            'semester_id' => 1,
            'title'       => 'Schnupper-Probe',
            'start'       => '2018-04-18 19:30:00',
            'end'         => '2018-04-18 21:45:00',
            'place'       => 'Hörsaal X',
            'binary_answer'   => true,
            'mandatory'   => true,
            'weight'      => 1.0,
        ]);*/

        //factory(App\Models\Rehearsal::class, 15)->create();

        /*foreach (\App\Models\Rehearsal::all() as $rehearsal) {
            if ($rehearsal->mandatory) {
                \App\Http\Controllers\RehearsalController::createAttendances($rehearsal);
            }
        }*/

        /* DB::table('gigs')->insert([
            'semester_id' => 1,
            'title'       => 'Frühkonzert',
            'description' => 'Echt verdammt früh!',
            'start'       => '2018-04-21 19:30:00',
            'end'         => '2018-04-21 21:00:00',
            'place'       => 'Franziskuskirche',
            'binary_answer'   => true,
        ]); */

        //factory(App\Models\Gig::class, 5)->create();
    }
}
