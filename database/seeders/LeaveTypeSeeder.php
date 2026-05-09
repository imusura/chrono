<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'              => 'Vacation',
                'has_allocation'    => true,
                'requires_approval' => false,
                'allow_carryover'   => true,
            ],
            [
                'name'              => 'Sick Day',
                'has_allocation'    => false,
                'requires_approval' => false,
                'allow_carryover'   => false,
            ],
            [
                'name'              => 'Paid Leave',
                'has_allocation'    => true,
                'requires_approval' => false,
                'allow_carryover'   => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
