<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function company(): View
    {
        return view('settings.company', [
            'settings' => $this->settings->all(),
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_vat_number' => ['nullable', 'string', 'max:32'],
            'company_cr_number' => ['nullable', 'string', 'max:32'],
            'company_address' => ['nullable', 'string', 'max:2000'],
            'default_vat_rate' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'zatca_environment' => ['required', 'in:sandbox,production'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        $this->settings->put($data);

        return back()->with('success', 'Company and tax settings saved.');
    }

    public function plans(): View
    {
        return view('settings.plans', [
            'plan' => $this->settings->plan(),
            'trialEndsAt' => $this->settings->trialEndsAt(),
            'daysLeft' => $this->settings->trialDaysLeft(),
        ]);
    }

    public function selectPlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'in:trial,plus,pro'],
        ]);

        $this->settings->set('plan', $data['plan']);
        if ($data['plan'] === 'trial' && ! $this->settings->trialEndsAt()) {
            $this->settings->set('trial_ends_at', now()->addDays(14)->toDateString());
        }

        return back()->with('success', 'Plan updated to '.ucfirst($data['plan']).'.');
    }
}
