<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorizeUsers('view');

        $users = User::query()
            ->where('organization_id', organization_id())
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizeUsers('create');

        return view('users.create', [
            'modules' => config('permissions.modules', []),
            'actions' => config('permissions.actions', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeUsers('create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        User::create([
            'organization_id' => organization_id(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'show_password' => $data['password'],
            'user_type' => User::TYPE_USER,
            'is_active' => $request->boolean('is_active', true),
            'permissions' => User::normalizePermissions($data['permissions'] ?? []),
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully. They can now sign in with their email and password.');
    }

    public function edit(int $id): View
    {
        $user = $this->orgUser($id);

        $this->authorizeUsers('update', $user);

        return view('users.edit', [
            'user' => $user,
            'modules' => config('permissions.modules', []),
            'actions' => config('permissions.actions', []),
            'isSelf' => $user->id === auth()->id(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->orgUser($id);

        $this->authorizeUsers('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($user->id === auth()->id()) {
            $user->is_active = true;
        } else {
            $user->is_active = $request->boolean('is_active', true);
            $user->permissions = User::normalizePermissions($data['permissions'] ?? []);
        }

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->show_password = $data['password'];
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeUsers('delete');

        $user = $this->orgUser($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User removed successfully.');
    }

    private function authorizeUsers(string $action, ?User $target = null): void
    {
        if ($action === 'update' && $target && $target->id === auth()->id()) {
            return;
        }

        if (! auth()->user()?->canModule('users', $action)) {
            abort(403, 'You do not have permission to manage users.');
        }
    }

    private function orgUser(int $id): User
    {
        return User::query()
            ->where('organization_id', organization_id())
            ->findOrFail($id);
    }
}
