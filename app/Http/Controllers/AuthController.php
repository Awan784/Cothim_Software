<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request, OrganizationProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $organization = Organization::create([
            'name' => $data['company_name'],
            'slug' => Organization::uniqueSlug($data['company_name']),
            'plan' => Organization::PLAN_TRIAL,
            'trial_ends_at' => now()->addDays((int) config('ams.trial_days', 14))->toDateString(),
            'default_vat_rate' => 15,
            'zatca_environment' => 'sandbox',
            'status' => 'active',
        ]);

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'show_password' => $data['password'],
            'user_type' => User::TYPE_ADMIN,
            'is_active' => true,
        ]);

        $provisioner->provision($organization);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to '.config('ams.product_name').'.');
    }

    public function processLoginRequest(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'This account is inactive or does not exist.');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Wrong email or password.');
        }

        $request->session()->regenerate();

        if ($request->user()->isPlatformAdmin()) {
            return redirect()->intended(route('platform.home'));
        }

        return redirect()->intended(route('dashboard'));
    }
}
