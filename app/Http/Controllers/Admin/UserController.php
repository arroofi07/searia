<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.mb_strtolower((string) $request->string('search')).'%';
                $query->where(function ($inner) use ($search): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('is_active'), function ($query) use ($request): void {
                $query->where('is_active', $request->string('is_active') === '1');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => [UserRole::Panitia, UserRole::Juri, UserRole::SuperAdmin],
            'filters' => $request->only(['search', 'role', 'is_active']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $defaultRole = $request->string('role')->toString();
        if (! in_array($defaultRole, [UserRole::Panitia->value, UserRole::Juri->value], true)) {
            $defaultRole = UserRole::Juri->value;
        }

        return view('admin.users.create', [
            'defaultRole' => $defaultRole,
            'roles' => [UserRole::Panitia, UserRole::Juri],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = [UserRole::Panitia, UserRole::Juri];
        if ($user->isSuperAdmin() && request()->user()?->isSuperAdmin()) {
            $roles = [UserRole::SuperAdmin, UserRole::Panitia, UserRole::Juri];
        }

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->user()?->id === $user->id && ($data['is_active'] ?? true) === false) {
            return back()->withErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->update(['is_active' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun dinonaktifkan.');
    }
}
