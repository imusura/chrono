<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@chrono.test'], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        $org = Organisation::create(['name' => 'Acme Corp']);

        $roles = [
            ['name' => 'Developer', 'color' => '#6366f1'],
            ['name' => 'Designer',  'color' => '#ec4899'],
            ['name' => 'Manager',   'color' => '#f59e0b'],
        ];

        [$dev, $des, $mgr] = collect($roles)->map(
            fn ($r) => Role::create([...$r, 'organisation_id' => $org->id])
        )->all();

        // Activities — some shared across roles
        $activities = [
            // Developer-specific
            ['name' => 'Backend Development', 'color' => '#3b82f6', 'roles' => [$dev->id]],
            ['name' => 'Code Review',         'color' => '#8b5cf6', 'roles' => [$dev->id]],
            ['name' => 'DevOps / Deployment', 'color' => '#06b6d4', 'roles' => [$dev->id]],
            // Designer-specific
            ['name' => 'UI Design',           'color' => '#f43f5e', 'roles' => [$des->id]],
            ['name' => 'Prototyping',         'color' => '#e879f9', 'roles' => [$des->id]],
            // Shared: Dev + Designer
            ['name' => 'Frontend Development','color' => '#10b981', 'roles' => [$dev->id, $des->id]],
            // Shared: Dev + Designer + Manager
            ['name' => 'Meetings',            'color' => '#f97316', 'roles' => [$dev->id, $des->id, $mgr->id]],
            // Manager-specific
            ['name' => 'Project Planning',    'color' => '#eab308', 'roles' => [$mgr->id]],
            ['name' => 'Reporting',           'color' => '#84cc16', 'roles' => [$mgr->id]],
            // Shared: Manager + Designer
            ['name' => 'Client Communication','color' => '#14b8a6', 'roles' => [$des->id, $mgr->id]],
        ];

        foreach ($activities as $data) {
            $roleIds = $data['roles'];
            $activity = Activity::create([
                'organisation_id' => $org->id,
                'name'            => $data['name'],
                'color'           => $data['color'],
                'is_active'       => true,
            ]);
            $activity->roles()->attach($roleIds);
        }

        $users = [
            ['name' => 'Alice Dev',    'email' => 'alice@chrono.test',   'role' => $dev],
            ['name' => 'Bob Designer', 'email' => 'bob@chrono.test',     'role' => $des],
            ['name' => 'Carol Manager','email' => 'carol@chrono.test',   'role' => $mgr],
        ];

        foreach ($users as $u) {
            $user = User::create([
                'name'             => $u['name'],
                'email'            => $u['email'],
                'password'         => Hash::make('password'),
                'organisation_id'  => $org->id,
                'contracted_hours' => 8.00,
            ]);
            $user->roles()->attach($u['role']->id);
        }
    }
}
