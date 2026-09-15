<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Carbon;

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
        'default_vat_rate' => 'default_vat_rate',
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
                'default_vat_rate' => config('ams.default_vat_rate', 15),
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
            'default_vat_rate' => $org->default_vat_rate,
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
