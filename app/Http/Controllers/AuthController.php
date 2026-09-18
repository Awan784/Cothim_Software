<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Salesman;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('salesman')->check()) {
            return redirect()->route('salesman.dashboard');
        }

        return view('auth.login', [
            'brandLogo' => $this->brandLogoUrl(),
        ]);
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
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $password = $data['password'];

        $user = User::query()->where('email', $login)->first();
        if ($user) {
            if (! $user->is_active) {
                return back()
                    ->withInput($request->only('login'))
                    ->with('error', 'This account is inactive or does not exist.');
            }

            if (Hash::check($password, $user->password)) {
                Auth::guard('salesman')->logout();
                Auth::guard('web')->login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                if ($user->isPlatformAdmin()) {
                    return redirect()->intended(route('platform.home'));
                }

                return redirect()->intended(route('dashboard'));
            }
        }

        $salesmen = Salesman::query()
            ->withoutGlobalScopes()
            ->where(function ($query) use ($login) {
                $query->where('username', $login);
                if (str_contains($login, '@')) {
                    $query->orWhere('email', $login);
                }
            })
            ->get();

        foreach ($salesmen as $salesman) {
            if (! $salesman->is_active || ! Hash::check($password, (string) $salesman->password)) {
                continue;
            }

            Auth::guard('web')->logout();
            Auth::guard('salesman')->login($salesman);
            $request->session()->regenerate();

            return redirect()->route('salesman.dashboard');
        }

        return back()
            ->withInput($request->only('login'))
            ->with('error', 'Wrong email, username, or password.');
    }

    private function brandLogoUrl(): ?string
    {
        $path = Organization::query()
            ->whereNotNull('logo_path')
            ->where('logo_path', '!=', '')
            ->orderBy('id')
            ->value('logo_path');

        if (! is_string($path) || $path === '' || ! is_file(public_path($path))) {
            return null;
        }

        return asset($path);
    }
}
