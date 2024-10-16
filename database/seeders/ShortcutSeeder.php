<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShortcutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shortcuts')->insert([
            'user_id' => 1,
            'name' => 'Profile',
            'route' => '/Profile',
            'customisation' => json_encode([
                'icon' => 1,
                'color' => 'blue',
                'color_hover' => 'red',
            ]),
        ]);
    }
}
