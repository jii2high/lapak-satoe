<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $categories = [
        'Makanan',
        'Minuman',
        'Kerajinan',
        'Jasa',
        'Fashion',
    ];

    public function run(): void
    {
        foreach ($this->categories as $name) {
            Category::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)],
            );
        }
    }
}
