<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\SwitchRoleRequest;
use App\Http\Requests\Account\UpdateDisplayNameRequest;
use App\Http\Requests\Account\UpdateEmailRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateUsernameRequest;
use App\Models\UserRole;
use App\Support\HomePath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $application = $user->sellerApplication;

        $roles = $user
            ->roles()
            ->get()
            ->map(fn (UserRole $role) => [
                'value' => $role->role->value,
                'label' => $role->role->label(),
            ])
            ->values()
            ->all();

        return Inertia::render('settings/account', [
            'roles' => $roles,
            'seller_application' => $application === null ? null : [
                'status' => $application->status->value,
            ],
        ]);
    }

    public function updateDisplayName(UpdateDisplayNameRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nama tampilan diperbarui.']);

        return back();
    }

    public function updateUsername(UpdateUsernameRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Username diperbarui.']);

        return back();
    }

    public function updateEmail(UpdateEmailRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->email = $request->validated('email');
        $user->email_verified_at = null;
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Email diperbarui. Verifikasi ulang lewat OTP.']);

        return back();
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kata sandi diperbarui.']);

        return back();
    }

    public function switchRole(SwitchRoleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['active_role' => $request->validated('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mode akun diganti.']);

        return redirect(HomePath::for($user));
    }
}
