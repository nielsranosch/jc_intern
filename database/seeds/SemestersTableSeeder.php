<?php

use Illuminate\Database\Seeder;

class SemestersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*DB::table('semesters')->insert([
            [
                'id' => 1,
                'start' => '2017-04-01',
                'end' => '2017-09-30',
                'label' => 'Sommersemester 17',
            ],
            [
                'id' => 2,
                'start' => '2017-10-01',
                'end' => '2018-03-31',
                'label' => 'Wintersemester 17/18',
            ],[
                'id' => 3,
                'start' => '2018-04-01',
                'end' => '2018-09-30',
                'label' => 'Sommersemester 18',
            ]
        ]);*/

        \App\Http\Controllers\SemesterController::getSemester(\Carbon\Carbon::now());
    }
}
