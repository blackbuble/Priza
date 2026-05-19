<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Priza',
            'email' => 'admin@priza.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        \App\Models\Coupon::factory(20)->create();

        \App\Models\LotterySetting::where('key', 'lottery_active')->update(['value' => '1']);
    }
}
