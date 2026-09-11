<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAdminController extends Controller
{
    public function home(): View
    {
        $orgs = Organization::query()->withCount('users')->orderByDesc('id')->get();

        return view('platform.home', [
            'organizations' => $orgs,
            'stats' => [
                'companies' => $orgs->count(),
                'users' => User::query()->count(),
                'trials' => $orgs->where('plan', Organization::PLAN_TRIAL)->count(),
                'active' => $orgs->where('status', 'active')->count(),
                'suspended' => $orgs->where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function organizations(): View
    {
        $organizations = Organization::query()
            ->withCount('users')
            ->orderByDesc('id')
            ->get();

        return view('platform.organizations', compact('organizations'));
    }

    public function show(Organization $organization): View
    {
        $organization->load('users');

        return view('platform.show', [
            'organization' => $organization,
            'invoices' => SalesInvoice::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
            'customers' => Customer::withoutGlobalScopes()->where('organization_id', $organization->id)->count(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'in:trial,plus,pro'],
            'trial_ends_at' => ['nullable', 'date'],
            'status' => ['required', 'in:active,suspended'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        $organization->update($data);

        return back()->with('success', __('Company updated.'));
    }

    public function extendTrial(Request $request, Organization $organization): RedirectResponse
    {
        $days = (int) $request->validate(['days' => ['required', 'integer', 'in:7,14,30']])['days'];
        $from = $organization->trial_ends_at && $organization->trial_ends_at->isFuture()
            ? $organization->trial_ends_at
            : now();
        $organization->update([
            'plan' => Organization::PLAN_TRIAL,
            'trial_ends_at' => $from->copy()->addDays($days)->toDateString(),
            'status' => 'active',
        ]);

        return back()->with('success', __('Trial extended by :days days.', ['days' => $days]));
    }

    public function users(): View
    {
        $users = User::query()
            ->with('organization')
            ->orderByDesc('id')
            ->get();

        return view('platform.users', compact('users'));
    }

    public function toggleUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot disable your own platform account.'));
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? __('User enabled.') : __('User disabled.'));
    }
}
