<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\ExpenseAccount;
use App\Models\NominalAccount;
use App\Models\Organization;

class OrganizationProvisioner
{
    public function provision(Organization $organization): void
    {
        $previous = app()->bound('current_organization_id') ? app('current_organization_id') : null;
        app()->instance('current_organization_id', $organization->id);

        try {
            $this->seedChartOfAccounts();
            $this->seedExpenseAccounts();
            $this->seedShopCashAccount();
        } finally {
            if ($previous) {
                app()->instance('current_organization_id', $previous);
            } else {
                app()->forgetInstance('current_organization_id');
            }
        }
    }

    private function seedChartOfAccounts(): void
    {
        $roots = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Income', 'type' => 'income'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense'],
        ];

        $rootIds = [];
        foreach ($roots as $row) {
            $rootIds[$row['type']] = NominalAccount::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            )->id;
        }

        $children = [
            ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1200', 'name' => 'Bank', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1300', 'name' => 'Accounts Receivable (Customers)', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '1400', 'name' => 'Inventory', 'type' => 'asset', 'parent_type' => 'asset'],
            ['code' => '2100', 'name' => 'Accounts Payable (Suppliers)', 'type' => 'liability', 'parent_type' => 'liability'],
            ['code' => '4100', 'name' => 'Sales', 'type' => 'income', 'parent_type' => 'income'],
            ['code' => '5100', 'name' => 'General Expenses', 'type' => 'expense', 'parent_type' => 'expense'],
        ];

        foreach ($children as $row) {
            NominalAccount::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'parent_id' => $rootIds[$row['parent_type']] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedShopCashAccount(): void
    {
        BankAccount::ensureShopCash();

        CashAccount::firstOrCreate(
            ['name' => 'Shop Cash'],
            [
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    private function seedExpenseAccounts(): void
    {
        foreach ([
            ['name' => 'Utility', 'description' => 'Electricity, gas, water'],
            ['name' => 'Rent', 'description' => 'Office or shop rent'],
            ['name' => 'Salaries', 'description' => 'Staff salaries'],
            ['name' => 'Transport', 'description' => 'Travel and delivery'],
        ] as $account) {
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
