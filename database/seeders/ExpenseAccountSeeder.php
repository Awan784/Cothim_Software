<?php

namespace Database\Seeders;

use App\Models\ExpenseAccount;
use Illuminate\Database\Seeder;

class ExpenseAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Utility', 'description' => 'Electricity, gas, water'],
            ['name' => 'Rent', 'description' => 'Office or shop rent'],
            ['name' => 'Salaries', 'description' => 'Staff salaries'],
            ['name' => 'Transport', 'description' => 'Travel and delivery'],
        ];

        foreach ($accounts as $account) {
            ExpenseAccount::firstOrCreate(
                ['name' => $account['name']],
                [
                    'description' => $account['description'],
                    'is_active' => true,
                    'total_spent' => 0,
                ]
            );
        }
    }
}
