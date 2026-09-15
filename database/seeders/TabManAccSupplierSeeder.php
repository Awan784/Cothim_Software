<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabManAccSupplierSeeder extends Seeder
{
    /** @var array<string, string> */
    private array $cityLookup = [];

    public function run(): void
    {
        $path = base_path('data.sql');
        if (! is_file($path)) {
            $this->command?->error('data.sql not found at project root.');

            return;
        }

        $sql = file_get_contents($path) ?: '';
        $purchaseRows = $this->insertRows($sql, 'dbo.TAB_MAN_ACC');
        $messerRows = $this->insertRows($sql, 'dbo.TAB_MES_HED');

        if ($purchaseRows === []) {
            $this->command?->error('No TAB_MAN_ACC purchase data found.');

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

        $this->buildCityLookup();

        $messers = [];
        foreach ($messerRows as $row) {
            $name = trim((string) ($row[1] ?? ''));
            if ($name === '' || $name === '-') {
                continue;
            }
            $messers[$name] = $row;
            $messers[$this->vendorKey($name)] = $row;
        }

        $vendors = [];
        foreach ($purchaseRows as $row) {
            $type = strtoupper(trim((string) ($row[14] ?? '')));
            if ($type !== 'PURCHASE' && $type !== 'PURCHASE RETURN') {
                continue;
            }

            $name = trim((string) ($row[3] ?? ''));
            if ($this->shouldSkip($name)) {
                continue;
            }

            $key = $this->vendorKey($name);
            if (! isset($vendors[$key])) {
                $vendors[$key] = ['name' => $name, 'count' => 0];
            }
            $vendors[$key]['count']++;
            if ($vendors[$key]['count'] === 1 || strlen($name) > strlen($vendors[$key]['name'])) {
                $vendors[$key]['name'] = $name;
            }
        }

        $imported = 0;
        $skipped = 0;

        foreach ($vendors as $key => $vendor) {
            $name = $vendor['name'];
            $detail = $messers[$name] ?? $messers[$key] ?? null;
            $cityFromName = $this->cityFromSuffix($name);

            $phone = null;
            $address = null;
            $city = $cityFromName;
            if ($detail) {
                $phone = $this->nullable($detail[5] ?? null) ?? $this->nullable($detail[7] ?? null) ?? $this->nullable($detail[6] ?? null);
                $address = $this->nullable($detail[9] ?? null);
                $city = $this->matchCity((string) ($detail[8] ?? '')) ?: $cityFromName;
            }

            $supplier = Supplier::query()->firstOrNew(['name' => $name]);
            $supplier->fill([
                'phone' => $phone ? Str::limit($phone, 50, '') : $supplier->phone,
                'address' => $address ?: $supplier->address,
                'city' => $city ?: $supplier->city,
                'is_active' => true,
            ]);
            if (! $supplier->exists) {
                $supplier->email = null;
                $supplier->vat_number = null;
                $supplier->opening_balance = 0;
                $supplier->current_balance = 0;
                $imported++;
            } else {
                $skipped++;
            }
            $supplier->save();
        }

        $this->command?->info("Imported {$imported} suppliers into {$org->name} (skipped {$skipped}).");
    }

    private function shouldSkip(string $name): bool
    {
        $upper = strtoupper($name);

        return $name === ''
            || $upper === '-'
            || $upper === 'STOCK ADJUSTMENT'
            || str_starts_with($upper, 'COUNTER CASH');
    }

    private function vendorKey(string $name): string
    {
        $key = strtoupper(trim($name));
        $key = preg_replace('/[.\s]+/', ' ', $key) ?: $key;

        return $key;
    }

    private function cityFromSuffix(string $name): ?string
    {
        if (! preg_match('/(?:\s|-|\()([A-Z]{3})\)?\s*$/i', $name, $match)) {
            return null;
        }

        return match (strtoupper($match[1])) {
            'LHE' => 'Lahore',
            'FSD' => 'Faisalabad',
            'GUJ' => 'Gujranwala',
            'KHI' => 'Karachi',
            'BGH' => 'Bagh',
            'RWP' => 'Rawalpindi',
            'MUX' => 'Multan',
            'PEW' => 'Peshawar',
            'ISB' => 'Islamabad',
            default => null,
        };
    }

    private function buildCityLookup(): void
    {
        foreach (config('pakistan.cities', []) as $city) {
            $this->cityLookup[strtoupper($city)] = $city;
        }
        $this->cityLookup['PESHAWER'] = 'Peshawar';
        $this->cityLookup['FAISALABBAD'] = 'Faisalabad';
        $this->cityLookup['FAISLABAD'] = 'Faisalabad';
        $this->cityLookup['FASIALABAD'] = 'Faisalabad';
        $this->cityLookup['HAIDERABAD'] = 'Hyderabad';
        $this->cityLookup['GRW'] = 'Gujranwala';
        $this->cityLookup['PAKPATTIN'] = 'Pakpattan';
        $this->cityLookup['PAKPATTAN'] = 'Pakpattan';
    }

    private function matchCity(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-') {
            return null;
        }

        $upper = strtoupper($raw);
        if (isset($this->cityLookup[$upper])) {
            return $this->cityLookup[$upper];
        }

        $main = trim((string) preg_replace('/\s+(DISTT?\.?|DISTRICT).*/i', '', $raw));
        if (isset($this->cityLookup[strtoupper($main)])) {
            return $this->cityLookup[strtoupper($main)];
        }

        return Str::title(strtolower($raw));
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '' || $text === '-' || $text === '0' || $text === '0.00') {
            return null;
        }

        return $text;
    }

    /**
     * @return list<list<mixed>>
     */
    private function insertRows(string $sql, string $table): array
    {
        $needle = 'INSERT INTO `'.$table.'` VALUES ';
        $rows = [];
        $offset = 0;

        while (($pos = strpos($sql, $needle, $offset)) !== false) {
            $start = $pos + strlen($needle);
            $next = strpos($sql, $needle, $start);
            $enable = strpos($sql, 'ALTER TABLE `'.$table.'` ENABLE KEYS', $start);
            $ends = array_values(array_filter([$next === false ? null : $next, $enable === false ? null : $enable], fn ($v) => $v !== null));
            $end = $ends === [] ? strlen($sql) : min($ends);
            $chunk = rtrim(trim(substr($sql, $start, $end - $start)), ';');
            $rows = array_merge($rows, $this->parseTuples($chunk));
            $offset = $start;
        }

        return $rows;
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

            $rows[] = array_map(fn ($value) => strcasecmp($value, 'NULL') === 0 ? null : $value, $fields);
        }

        return $rows;
    }
}
