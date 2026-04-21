<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electrician',    'name_so' => 'Koronte-tifafin', 'icon' => '⚡'],
            ['name' => 'Plumber',        'name_so' => 'Baayib-tifafin',  'icon' => '🔧'],
            ['name' => 'Carpenter',      'name_so' => 'Najaar',           'icon' => '🪚'],
            ['name' => 'Painter',        'name_so' => 'Rinji-tifafin',    'icon' => '🖌️'],
            ['name' => 'AC Technician',  'name_so' => 'AC Farsamaale',    'icon' => '❄️'],
            ['name' => 'Mason',          'name_so' => 'Dhisme',           'icon' => '🧱'],
            ['name' => 'Welder',         'name_so' => 'Xidid-saar',       'icon' => '🔩'],
            ['name' => 'Driver',         'name_so' => 'Darawalka',        'icon' => '🚗'],
            ['name' => 'Cleaner',        'name_so' => 'Nadiifinle',       'icon' => '🧹'],
            ['name' => 'Security Guard', 'name_so' => 'Gaashaanle',       'icon' => '🛡️'],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->insertOrIgnore(
                array_merge($cat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
