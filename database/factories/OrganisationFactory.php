<?php

namespace Database\Factories;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organisation> */
class OrganisationFactory extends Factory
{
    protected $model = Organisation::class;

    public function definition(): array
    {
        return [
            'name'            => fake()->company(),
            'time_entry_mode' => 'range',
            'country_code'    => 'HR',
            'vacation_mode'   => 'simple',
            'year_reset_date' => '01-01',
        ];
    }
}
