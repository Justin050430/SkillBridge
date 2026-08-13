<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: model events are intentionally left enabled so the HasSlug trait
     * (which fills slugs on the "saving" event) works while seeding.
     */
    public function run(): void {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
