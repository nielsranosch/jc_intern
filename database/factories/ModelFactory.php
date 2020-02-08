<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Illuminate\Support\Str;

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->safeEmail,
        'password' => bcrypt(Str::random(10)),
        'voice_id' => $faker->numberBetween(5,12),
        'address_street' => $faker->streetAddress,
        'address_zip' => $faker->postcode,
        'address_city' => $faker->city,
        'birthday' => $faker->date(),
        'last_echo' => 1,
        'pseudo_id' => Str::random(20),
        'pseudo_password' => Str::random(222),
        'phone' => $faker->phoneNumber,
    ];
});

$factory->define(App\Models\Rehearsal::class, function (Faker\Generator $faker) {
    // Make sure end is after start dateTime.
    $start = $faker->dateTimeBetween('20.04.2018', '29.09.2018');

    return [
        'semester_id' => 1,
        'title'       => 'Probe',
        'description' => $faker->sentence(14),
        'start'       => $start,
        'end'         => $faker->dateTimeBetween($start, '29.09.2018'),
        'place'       => $faker->address,
        'mandatory'   => $faker->boolean(90),
        'weight'      => $faker->randomFloat(1, 0.0, 1.0),
    ];
});

$factory->define(App\Models\Gig::class, function (Faker\Generator $faker) {
    // Make sure end is after start dateTime.
    $start = $faker->dateTimeBetween('30.04.2018', '29.09.2018');

    return [
        'semester_id' => 1,
        'title'       => 'Konzert',
        'description' => $faker->sentence(14),
        'start'       => $start,
        'end'         => $faker->dateTimeBetween($start, '29.09.2018'),
        'place'       => $faker->address,
    ];
});
