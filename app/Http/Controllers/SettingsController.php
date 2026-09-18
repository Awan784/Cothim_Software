<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function company(): View
    {
        return view('settings.company', [
            'settings' => $this->settings->all(),
            'logoUrl' => $this->settings->logoUrl(),
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_vat_number' => ['nullable', 'string', 'max:32'],
            'company_cr_number' => ['nullable', 'string', 'max:32'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:2000'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'default_vat_rate' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'company_retain_percent' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'salesman_commission_percent' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        unset($data['company_logo'], $data['remove_logo']);
        $this->settings->put($data);

        $org = $this->settings->organization();

        if ($request->boolean('remove_logo') && $org?->logo_path) {
            $this->settings->deleteLogoFile($org->logo_path);
            $this->settings->put(['company_logo' => null]);
            $org = $this->settings->organization();
        }

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            if ($file instanceof UploadedFile) {
                $this->settings->storeLogoFile($file);
            }
        }

        return back()->with('success', 'Company and tax settings saved.');
    }
}
