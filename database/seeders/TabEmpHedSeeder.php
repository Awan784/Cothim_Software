<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Salesman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabEmpHedSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'abc123';

    public function run(): void
    {
        $path = base_path('data.sql');
        if (! is_file($path)) {
            $this->command?->error('data.sql not found at project root.');

            return;
        }

        $sql = file_get_contents($path) ?: '';
        if (! preg_match('/INSERT INTO `dbo\.TAB_EMP_HED` VALUES\s*(.+?)\/\*\!40000 ALTER TABLE `dbo\.TAB_EMP_HED` ENABLE KEYS \*\//s', $sql, $match)) {
            $this->command?->error('No TAB_EMP_HED insert data found in data.sql.');

            return;
        }

        $rows = $this->parseTuples(rtrim(trim($match[1]), ';'));
        if ($rows === []) {
            $this->command?->warn('TAB_EMP_HED has no rows to import.');

            return;
        }

        $org = Organization::query()->whereHas('users')->orderBy('id')->first()
            ?? Organization::query()->orderBy('id')->first();

        if (! $org) {
            $this->command?->error('No organization found. Seed the database first.');

            return;
        }

        app()->instance('current_organization_id', $org->id);
        app()->instance('current_organization', $org);

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row[1] ?? ''));
            if ($name === '' || $name === '-') {
                $skipped++;

                continue;
            }

            $salesman = Salesman::query()->firstOrNew(['name' => $name]);
            $isNew = ! $salesman->exists;

            if ($isNew) {
                $salesman->username = $this->uniqueUsername($name);
                $salesman->password = self::DEFAULT_PASSWORD;
                $salesman->show_password = self::DEFAULT_PASSWORD;
                $salesman->city = null;
                $salesman->monthly_target = 0;
            }

            $salesman->phone = $this->nullable($row[6] ?? null);
            $salesman->mobile = $this->nullable($row[7] ?? null);
            $salesman->is_active = true;
            $salesman->save();
            $imported++;
        }

        $this->command?->info("Imported {$imported} salesmen into {$org->name} (skipped {$skipped}). Default password: ".self::DEFAULT_PASSWORD);
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::slug($name, '.') ?: 'salesman';
        $username = $base;
        $i = 2;

        while (Salesman::query()->where('username', $username)->exists()) {
            $username = $base.$i;
            $i++;
        }

        return $username;
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);

        return ($text === '' || $text === '-') ? null : $text;
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseTuples(string $values): array
    {
        $rows = [];
        $len = strlen($values);
        $i = 0;

        while ($i < $len) {
            while ($i < $len && $values[$i] !== '(') {
                $i++;
            }
            if ($i >= $len) {
                break;
            }
            $i++;

            $fields = [];
            $current = '';
            $inQuote = false;

            while ($i < $len) {
                $ch = $values[$i];

                if ($inQuote) {
                    if ($ch === '\\' && $i + 1 < $len) {
                        $current .= $values[$i + 1];
                        $i += 2;
                        continue;
                    }
                    if ($ch === "'") {
                        if ($i + 1 < $len && $values[$i + 1] === "'") {
                            $current .= "'";
                            $i += 2;
                            continue;
                        }
                        $inQuote = false;
                        $i++;
                        continue;
                    }
                    $current .= $ch;
                    $i++;
                    continue;
                }

                if ($ch === "'") {
                    $inQuote = true;
                    $i++;
                    continue;
                }
                if ($ch === ',') {
                    $fields[] = trim($current);
                    $current = '';
                    $i++;
                    continue;
                }
                if ($ch === ')') {
                    $fields[] = trim($current);
                    $i++;
                    break;
                }

                $current .= $ch;
                $i++;
            }

            $rows[] = array_map(function ($value) {
                return strcasecmp($value, 'NULL') === 0 ? null : $value;
            }, $fields);
        }

        return $rows;
    }
}
