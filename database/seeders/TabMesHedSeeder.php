<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabMesHedSeeder extends Seeder
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
        if (! preg_match('/INSERT INTO `dbo\.TAB_MES_HED` VALUES\s*(.+?)\/\*\!40000 ALTER TABLE `dbo\.TAB_MES_HED` ENABLE KEYS \*\//s', $sql, $match)) {
            $this->command?->error('No TAB_MES_HED insert data found in data.sql.');

            return;
        }

        $rows = $this->parseTuples(rtrim(trim($match[1]), ';'));
        if ($rows === []) {
            $this->command?->warn('TAB_MES_HED has no rows to import.');

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

        $existing = Customer::query()
            ->whereNotNull('company_name')
            ->pluck('id', 'company_name');

        $imported = 0;
        $skipped = 0;
        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            $company = trim((string) ($row[1] ?? ''));
            if ($company === '' || $company === '-') {
                $skipped++;

                continue;
            }

            if ($existing->has($company)) {
                $skipped++;

                continue;
            }

            $contact = $this->nullable($row[12] ?? null) ?? $company;
            $mobile = $this->nullable($row[5] ?? null);
            $mobile2 = $this->nullable($row[6] ?? null);
            $phone = $this->nullable($row[7] ?? null) ?? $mobile2;
            $code = $this->nullable($row[4] ?? null);
            if ($code === '0') {
                $code = null;
            }

            $category = strtoupper(trim((string) ($row[11] ?? '')));
            $area = $this->nullable($row[10] ?? null);
            if (in_array($category, ['PATIENT', 'PAITENT'], true)) {
                $area = $area ? $area.' · Patient' : 'Patient';
            }

            $batch[] = [
                'organization_id' => $org->id,
                'name' => Str::limit($contact, 255, ''),
                'company_name' => Str::limit($company, 255, ''),
                'proprietor_name' => $contact !== $company ? Str::limit($contact, 255, '') : null,
                'phone' => $phone ? Str::limit($phone, 50, '') : null,
                'mobile' => $mobile ? Str::limit($mobile, 50, '') : null,
                'email' => null,
                'ntn' => null,
                'strn' => null,
                'vat_number' => null,
                'license_no' => $code ? Str::limit($code, 100, '') : null,
                'address' => $this->nullable($row[9] ?? null),
                'city' => $this->matchCity((string) ($row[8] ?? '')),
                'area' => $area ? Str::limit($area, 150, '') : null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $existing[$company] = 0;
            $imported++;

            if (count($batch) >= 250) {
                Customer::insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            Customer::insert($batch);
        }

        $this->command?->info("Imported {$imported} customers into {$org->name} (skipped {$skipped}).");
    }

    private function buildCityLookup(): void
    {
        foreach (config('pakistan.cities', []) as $city) {
            $this->cityLookup[strtoupper($city)] = $city;
        }

        $aliases = [
            'PESHAWER' => 'Peshawar',
            'FAISALABBAD' => 'Faisalabad',
            'FAISALABAD' => 'Faisalabad',
            'HAIDERABAD' => 'Hyderabad',
            'HYDERABAD' => 'Hyderabad',
            'DERA ISMAEEL KHAN' => 'Dera Ismail Khan',
            'D I KHAN' => 'Dera Ismail Khan',
            'DIKHAN' => 'Dera Ismail Khan',
            'MANSEHRAH' => 'Mansehra',
            'JEHLUM' => 'Jhelum',
            'KOTLI A.K' => 'Kotli',
            'KOTLI A.K.' => 'Kotli',
            'KOTLI AK' => 'Kotli',
            'KAND KOT' => 'Kandhkot',
            'KANDHKOT' => 'Kandhkot',
            'TANDO ADAM' => 'Tando Adam',
            'NANKANA SAHIB' => 'Nankana Sahib',
            'RAHIM YAR KHAN' => 'Rahim Yar Khan',
            'RAWAL PINDI' => 'Rawalpindi',
            'WAZIR ABAD' => 'Wazirabad',
            'M.B DIN' => 'Mandi Bahauddin',
            'M B DIN' => 'Mandi Bahauddin',
            'MANDI BAHAUDIN' => 'Mandi Bahauddin',
            'DG KHAN' => 'Dera Ghazi Khan',
            'D.G KHAN' => 'Dera Ghazi Khan',
            'D.G. KHAN' => 'Dera Ghazi Khan',
        ];

        foreach ($aliases as $from => $to) {
            $this->cityLookup[$from] = $to;
        }
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

        $main = trim((string) preg_replace('/\s+(DISTT?\.?|DISTRICT|DIVISION|TEHSIL).*/i', '', $raw));
        $mainUpper = strtoupper($main);
        if (isset($this->cityLookup[$mainUpper])) {
            return $this->cityLookup[$mainUpper];
        }

        $best = null;
        $bestLen = 0;
        foreach ($this->cityLookup as $key => $city) {
            if (strlen($key) < 4) {
                continue;
            }
            if (str_contains($upper, $key) && strlen($key) > $bestLen) {
                $best = $city;
                $bestLen = strlen($key);
            }
        }

        if ($best) {
            return $best;
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
