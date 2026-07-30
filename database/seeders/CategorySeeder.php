<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Programming', 'description' => 'Software development and programming languages'],
            ['category_name' => 'Cybersecurity', 'description' => 'Security principles, ethical hacking and defence'],
            ['category_name' => 'Web Development', 'description' => 'Frontend and backend web technologies'],
            ['category_name' => 'Mobile Development', 'description' => 'Android, iOS and cross-platform development'],
            ['category_name' => 'Data Science', 'description' => 'Data analysis, AI and machine learning'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['category_name' => $category['category_name']],
                ['description' => $category['description']]
            );
        }
    }
}