<?php

namespace Database\Seeders;

use App\Models\NominalAccount;
use Illuminate\Database\Seeder;

class NominalAccountSeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Income', 'type' => 'income'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense'],
        ];

        $rootIds = [];
        foreach ($roots as $r) {
            $rootIds[$r['type']] = NominalAccount::firstOrCreate(
                ['code' => $r['code']],
                ['name' => $r['name'], 'type' => $r['type'], 'is_active' => true]
            )->id;
        }

        $children = [
            // Assets
            ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1200', 'name' => 'Bank', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1300', 'name' => 'Accounts Receivable (Customers)', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1400', 'name' => 'Inventory', 'type' => 'asset', 'parent_type' => 'asset'],

            // Liabilities
            ['code' => '2100', 'name' => 'Accounts Payable (Suppliers)', 'type' => 'liability', 'parent_type' => 'liability'],

            // Income
            ['code' => '4100', 'name' => 'Sales', 'type' => 'income', 'parent_type' => 'income'],

            // Expenses
            ['code' => '5100', 'name' => 'General Expenses', 'type' => 'expense', 'parent_type' => 'expense'],
        ];

        foreach ($children as $c) {
            NominalAccount::firstOrCreate(
                ['code' => $c['code']],
                [
                    'name' => $c['name'],
                    'type' => $c['type'],
                    'parent_id' => $rootIds[$c['parent_type']] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}

