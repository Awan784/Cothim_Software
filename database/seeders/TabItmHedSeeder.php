<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\StockCategory;
use App\Models\StockItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabItmHedSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('data.sql');
        if (! is_file($path)) {
            $this->command?->error('data.sql not found at project root.');

            return;
        }

        $sql = file_get_contents($path) ?: '';
        if (! preg_match('/INSERT INTO `dbo\.TAB_ITM_HED` VALUES\s*(.+?)\/\*\!40000 ALTER TABLE `dbo\.TAB_ITM_HED` ENABLE KEYS \*\//s', $sql, $match)) {
            $this->command?->error('No TAB_ITM_HED insert data found in data.sql.');

            return;
        }

        $rows = $this->parseTuples(rtrim(trim($match[1]), ';'));
        if ($rows === []) {
            $this->command?->warn('TAB_ITM_HED has no rows to import.');

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

            $categoryName = $this->categoryName((string) ($row[12] ?? ''));
            $kind = $this->categoryKind($categoryName);
            $category = StockCategory::query()->firstOrCreate(
                ['name' => $categoryName],
                [
                    'kind' => $kind,
                    'description' => 'Imported from TAB_ITM_HED',
                    'is_active' => true,
                ]
            );

            $oldId = (int) ($row[0] ?? 0);
            $sku = $oldId > 0 ? 'ITM-'.$oldId : null;
            $payload = [
                'stock_category_id' => $category->id,
                'sku' => $sku,
                'name' => $name,
                'unit' => 'pcs',
                'quantity' => 0,
                'cost_price' => $this->money($row[3] ?? 0),
                'sale_price' => $this->money($row[4] ?? 0),
                'batch_no' => $this->nullable($row[6] ?? null),
                'pack_size' => $this->packSize($row[5] ?? null, $name),
                'manufacturer' => $this->nullable($row[13] ?? null) ?? $this->nullable($row[14] ?? null),
                'expiry_date' => $this->expiry($row[7] ?? null),
                'composition' => $this->nullable($row[16] ?? null),
                'barcode' => $this->nullable($row[17] ?? null),
                'description' => $this->description($row),
                'reorder_level' => is_numeric($row[10] ?? null) ? (int) $row[10] : null,
                'is_active' => true,
            ];

            $item = $sku
                ? StockItem::query()->firstOrNew(['sku' => $sku])
                : StockItem::query()->firstOrNew(['name' => $name, 'stock_category_id' => $category->id]);

            $item->fill($payload);
            $item->save();
            $imported++;
        }

        $this->command?->info("Imported {$imported} items into {$org->name} (skipped {$skipped}).");
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
                if (strcasecmp($value, 'NULL') === 0) {
                    return null;
                }

                return $value;
            }, $fields);
        }

        return $rows;
    }

    private function categoryName(string $raw): string
    {
        $name = strtoupper(trim($raw));
        if ($name === '' || $name === '-') {
            return 'Uncategorized';
        }
        if ($name === 'COMNETICS') {
            $name = 'COSMETICS';
        }

        return Str::title(strtolower($name));
    }

    private function categoryKind(string $categoryName): string
    {
        return str_contains(strtolower($categoryName), 'cosmetic') ? 'cream' : 'other';
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '' || $text === '-' || $text === '0' || $text === '0.00') {
            return null;
        }

        return $text;
    }

    private function packSize(mixed $packing, string $name): ?string
    {
        if (preg_match('/(\d+\s?(?:ml|gm|g|mg|tab|caps)?)/i', $name, $match)) {
            return trim($match[1]);
        }

        $packing = trim((string) $packing);
        if ($packing === '' || $packing === '0') {
            return null;
        }

        return $packing;
    }

    private function expiry(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            $date = Carbon::parse($text)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ((int) $date->year < 2000 || (int) $date->year > 2100) {
            return null;
        }

        return $date->toDateString();
    }

    /**
     * @param  list<mixed>  $row
     */
    private function description(array $row): ?string
    {
        $parts = [];
        $urdu = $this->nullable($row[2] ?? null);
        if ($urdu) {
            $parts[] = $urdu;
        }
        $note = $this->nullable($row[15] ?? null);
        if ($note) {
            $parts[] = $note;
        }
        $packing = trim((string) ($row[5] ?? ''));
        if ($packing !== '' && $packing !== '0' && $packing !== '1') {
            $parts[] = 'Packing: '.$packing;
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
