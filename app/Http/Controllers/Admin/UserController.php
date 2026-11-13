<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserEmailRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserService;
use App\Support\EmailMask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $pattern = '%'.addcslashes($query, '%_\\').'%';

        $users = User::query()
            ->with('roles')
            ->when($query !== '', fn (Builder $sub) => $sub->where(fn (Builder $words) => $words
                ->where('username', 'like', $pattern)
                ->orWhere('display_name', 'like', $pattern)
                ->orWhere('email', 'like', $pattern)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'username' => $user->username,
                'email' => EmailMask::mask($user->email),
                'roles' => $user->roles
                    ->map(fn (UserRole $role) => $role->role->label())
                    ->values()
                    ->all(),
                'is_active' => $user->is_active,
                'is_self' => $request->user()->is($user),
                'is_admin' => $user->roles->contains(
                    fn (UserRole $role) => $role->role === Role::Admin,
                ),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'filters' => [
                'q' => $query,
            ],
        ]);
    }

    public function updateEmail(UpdateUserEmailRequest $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Email akunmu diubah dari halaman akun.');

        $user->email = $request->validated('email');
        $user->email_verified_at = null;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Email @{$user->username} diperbarui. Verifikasinya direset.",
        ]);

        return back();
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Kata sandi akunmu diubah dari halaman akun.');

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tautan reset gagal dikirim. Coba lagi.',
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Tautan reset kata sandi dikirim ke email @{$user->username}.",
        ]);

        return back();
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($this->isProtected($request, $user), 403, 'Akun admin tidak bisa dinonaktifkan dari halaman ini.');

        $this->users->setActive($user, ! $user->is_active);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->is_active
                ? "Akun @{$user->username} diaktifkan."
                : "Akun @{$user->username} dinonaktifkan dan sesinya diputus.",
        ]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($this->isProtected($request, $user), 403, 'Akun admin tidak bisa dihapus dari halaman ini.');

        try {
            $this->users->delete($user);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Akun @{$user->username} dihapus.",
        ]);

        return back();
    }

    private function isProtected(Request $request, User $user): bool
    {
        return $request->user()->is($user) || $user->hasRole(Role::Admin);
    }
}
