<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SettingsService
{
    /**
     * @var array<string, string>
     */
    private const FIELD_MAP = [
        'company_name' => 'name',
        'company_vat_number' => 'vat_number',
        'company_cr_number' => 'cr_number',
        'company_address' => 'address',
        'company_phone' => 'phone',
        'company_logo' => 'logo_path',
        'default_vat_rate' => 'default_vat_rate',
        'company_retain_percent' => 'company_retain_percent',
        'salesman_commission_percent' => 'salesman_commission_percent',
        'plan' => 'plan',
        'trial_ends_at' => 'trial_ends_at',
        'zatca_environment' => 'zatca_environment',
        'whatsapp' => 'whatsapp',
    ];

    public function organization(): ?Organization
    {
        return current_organization();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $org = $this->organization();
        if (! $org) {
            return [
                'company_name' => config('ams.company_name'),
                'company_vat_number' => '',
                'company_cr_number' => '',
                'company_address' => '',
                'company_phone' => '',
                'company_logo' => '',
                'default_vat_rate' => config('ams.default_vat_rate', 15),
                'company_retain_percent' => 50,
                'salesman_commission_percent' => 25,
                'plan' => 'trial',
                'trial_ends_at' => null,
                'zatca_environment' => 'sandbox',
                'whatsapp' => '',
            ];
        }

        return [
            'company_name' => $org->name,
            'company_vat_number' => $org->vat_number,
            'company_cr_number' => $org->cr_number,
            'company_address' => $org->address,
            'company_phone' => $org->phone,
            'company_logo' => $org->logo_path,
            'default_vat_rate' => $org->default_vat_rate,
            'company_retain_percent' => $org->company_retain_percent ?? 50,
            'salesman_commission_percent' => $org->salesman_commission_percent ?? 25,
            'plan' => $org->plan,
            'trial_ends_at' => optional($org->trial_ends_at)?->toDateString(),
            'zatca_environment' => $org->zatca_environment,
            'whatsapp' => $org->whatsapp,
        ];
    }

    public function set(string $key, mixed $value): void
    {
        $this->put([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        $org = $this->organization();
        if (! $org) {
            return;
        }

        $payload = [];
        foreach ($values as $key => $value) {
            $field = self::FIELD_MAP[$key] ?? null;
            if (! $field) {
                continue;
            }
            $payload[$field] = $value === '' ? null : $value;
        }

        if ($payload === []) {
            return;
        }

        $org->fill($payload);
        $org->save();
        app()->instance('current_organization', $org->fresh());
    }

    public function companyName(): string
    {
        return (string) ($this->get('company_name') ?: config('ams.product_name', 'Contimade Traders'));
    }

    public function address(): string
    {
        return trim((string) $this->get('company_address', ''));
    }

    public function phone(): string
    {
        return trim((string) $this->get('company_phone', ''));
    }

    public function logoUrl(): ?string
    {
        $path = $this->publicLogoPath();
        if ($path === null) {
            return null;
        }

        return asset($path).'?v='.filemtime(public_path($path));
    }

    public function storeLogoFile(UploadedFile $file): string
    {
        $org = $this->organization();
        $this->deleteLogoFile($org?->logo_path);

        $directory = public_path('uploads/logos');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $filename = 'org-'.($org?->id ?: 'x').'-'.Str::lower(Str::random(12)).'.'.$extension;
        $file->move($directory, $filename);

        $path = 'uploads/logos/'.$filename;
        $this->put(['company_logo' => $path]);

        return $path;
    }

    public function deleteLogoFile(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $path = ltrim($path, '/');
        $candidates = [
            public_path($path),
            public_path('uploads/'.$path),
            storage_path('app/public/'.$path),
        ];

        foreach ($candidates as $fullPath) {
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    public function publicLogoPath(): ?string
    {
        $path = $this->get('company_logo');
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = ltrim($path, '/');
        if (is_file(public_path($path))) {
            return $path;
        }

        $legacy = storage_path('app/public/'.$path);
        if (! is_file($legacy)) {
            return null;
        }

        $directory = public_path('uploads/logos');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = basename($path);
        $publicPath = 'uploads/logos/'.$filename;
        if (! is_file(public_path($publicPath))) {
            copy($legacy, public_path($publicPath));
        }

        if ($path !== $publicPath) {
            $this->put(['company_logo' => $publicPath]);
        }

        return $publicPath;
    }

    /**
     * @return list<string>
     */
    public function printMetaLines(): array
    {
        $lines = [];
        if ($this->address() !== '') {
            $lines[] = $this->address();
        }

        $details = [];
        if ($this->phone() !== '') {
            $details[] = 'Phone '.$this->phone();
        }
        if ($this->get('company_vat_number')) {
            $details[] = 'NTN '.$this->get('company_vat_number');
        }
        if ($this->get('company_cr_number')) {
            $details[] = 'CR '.$this->get('company_cr_number');
        }
        if ($details !== []) {
            $lines[] = implode(' · ', $details);
        }

        return $lines;
    }

    public function contactLine(): string
    {
        return collect([$this->address() ?: null, $this->phone() ? 'Phone '.$this->phone() : null])
            ->filter()
            ->join(' · ') ?: '—';
    }

    public function vatRate(): float
    {
        return (float) ($this->get('default_vat_rate', 15) ?: 15);
    }

    public function plan(): string
    {
        return (string) ($this->get('plan', 'trial') ?: 'trial');
    }

    public function trialEndsAt(): ?Carbon
    {
        $value = $this->get('trial_ends_at');

        return $value ? Carbon::parse($value)->endOfDay() : null;
    }

    public function trialDaysLeft(): ?int
    {
        return $this->organization()?->trialDaysLeft();
    }

    public function isOnTrial(): bool
    {
        return $this->plan() === Organization::PLAN_TRIAL;
    }
}
