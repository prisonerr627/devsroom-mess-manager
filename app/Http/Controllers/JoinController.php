<?php

namespace App\Http\Controllers;

use App\Http\Requests\Join\JoinMessRequest;
use App\Models\Member;
use App\Models\Mess;
use App\Support\MemberStatus;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Post-signup chooser: a user with no mess either joins an existing one
 * with its join code (becoming a mess-member) or creates a new one
 * (the onboarding form — becoming its manager).
 */
class JoinController extends Controller
{
    public function choose(Request $request): View|RedirectResponse
    {
        if (Mess::activeId() !== null) {
            return redirect('/');
        }

        return view('join.choose');
    }

    public function showJoin(): View|RedirectResponse
    {
        if (Mess::activeId() !== null) {
            return redirect('/');
        }

        return view('join.code');
    }

    public function join(JoinMessRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $mess = Mess::findByJoinCode($data['join_code']);
        if (! $mess) {
            throw ValidationException::withMessages([
                'join_code' => __('No active mess matches that join code. Check it with your manager.'),
            ]);
        }

        // Pin the tenant for the rest of this request: the user's mess_id is
        // not set yet, and Member's slug generator + uniqueness checks below
        // must run against the mess being joined.
        Mess::setActiveId($mess->id);

        $mobileTaken = Member::query()->where('mobile', $data['mobile'])->exists();
        if ($mobileTaken) {
            throw ValidationException::withMessages([
                'mobile' => __('A member with this mobile number already exists in that mess. Ask your manager to link your login instead.'),
            ]);
        }

        $emailTaken = $user->email && Member::query()->where('email', $user->email)->whereNull('user_id')->exists();

        DB::transaction(function () use ($mess, $user, $data, $emailTaken): void {
            if ($emailTaken) {
                // The manager already added this person (by email) without a
                // login — link that record instead of creating a duplicate.
                $member = Member::query()->where('email', $user->email)->whereNull('user_id')->first();
                $member->update(['user_id' => $user->id, 'mobile' => $data['mobile']]);
            } else {
                Member::create([
                    'mess_id' => $mess->id,
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'email' => $user->email,
                    'room_or_seat' => $data['room_or_seat'] ?? null,
                    'joining_date' => now()->toDateString(),
                    'status' => MemberStatus::ACTIVE,
                ]);
            }

            $user->forceFill([
                'mess_id' => $mess->id,
                // They chose their own password at signup — no forced reset.
                'password_changed_at' => $user->password_changed_at ?? now(),
            ])->save();

            $user->assignRole(Role::firstOrCreate(['slug' => 'mess-member'], ['name' => 'Mess Member']));
        });

        Mess::forgetActiveIdCache();

        return redirect('/my')->with('success', __('Welcome to :mess!', ['mess' => $mess->name]));
    }
}
