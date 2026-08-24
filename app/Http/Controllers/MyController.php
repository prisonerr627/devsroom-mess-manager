<?php

namespace App\Http\Controllers;

use App\Http\Requests\My\ChangeMyPasswordRequest;
use App\Http\Requests\My\SaveMyTodayMealRequest;
use App\Http\Requests\My\StoreMealOffRequest;
use App\Http\Requests\My\StoreMyGuestMealRequest;
use App\Http\Requests\My\UpdateMyProfileRequest;
use App\Models\GuestMeal;
use App\Models\MealOffRequest;
use App\Models\Mess;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Services\GuestMealService;
use App\Services\MealGridService;
use App\Services\MemberDashboardService;
use App\Support\MealGridPrefs;
use App\Support\MealOffStatus;
use App\Support\StorageProvider;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MyController extends Controller
{
    public function __construct(
        private readonly MemberDashboardService $dashboards,
        private readonly MealGridService $mealGrid,
        private readonly GuestMealService $guestMeals,
    ) {}

    public function index(Request $request): View
    {
        $member = $request->user()->getMemberOrNull();
        $tab = $request->query('tab', 'overview');

        if (! $member) {
            return view('my.no-member');
        }

        $data = ['member' => $member, 'tab' => $tab];

        if ($tab === 'overview') {
            $data['overview'] = $this->dashboards->overviewCards($request->user());
        }

        if ($tab === 'meals') {
            $now = Carbon::now(config('app.timezone'));

            $data['mealEntries'] = $member->mealEntries()
                ->whereBetween('date', [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()])
                ->orderBy('date', 'desc')
                ->get();

            // Today's self-service card: current entry (or the admin's
            // pre-tick defaults when nothing is saved yet, mirroring the
            // manager grid), whether this member may edit today, and the
            // meals the admin shows on the grid.
            $todayEntry = $data['mealEntries']->first(fn ($entry) => $entry->date->toDateString() === $now->toDateString());
            $canEditToday = $this->canWriteToday($member->id, $now);
            $defaultOn = MealGridPrefs::defaultOn();

            $data['visibleMeals'] = MealGridPrefs::visibleMeals();
            $data['canEditToday'] = $canEditToday;
            $data['todayMeals'] = collect($data['visibleMeals'])->mapWithKeys(fn ($meal) => [
                $meal => $todayEntry?->{$meal} ?? ($todayEntry === null && $canEditToday && $defaultOn[$meal]),
            ])->all();
            $data['myGuestMeals'] = GuestMeal::query()
                ->where('member_id', $member->id)
                ->whereBetween('date', [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()])
                ->orderBy('date', 'desc')
                ->latest('id')
                ->get();
        }

        if ($tab === 'meal-off') {
            $data['mealOffRequests'] = $member->mealOffRequests()
                ->orderBy('requested_at', 'desc')
                ->limit(20)
                ->get();
        }

        if ($tab === 'payments') {
            $data['payments'] = Payment::query()
                ->where('member_id', $member->id)
                ->with('enteredBy')
                ->latest('date')
                ->latest('id')
                ->paginate(30);
        }

        return view('my', $data);
    }

    public function updateProfile(UpdateMyProfileRequest $request): RedirectResponse
    {
        $member = $request->user()->getMemberOrNull();
        if (! $member) {
            return redirect()->route('my')->with('error', __('Your mess account is not set up.'));
        }

        $data = $request->validated();
        $photo = $data['photo'] ?? null;
        unset($data['photo'], $data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        $member->update($data);

        // Update User record name + email if provided
        $user = $request->user();
        $userData = [];
        if (isset($data['name'])) {
            $userData['name'] = $data['name'];
        }
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $userData['email'] = $data['email'];
        }
        if (! empty($userData)) {
            $user->update($userData);
        }

        // Handle password change (forceFill: password_changed_at is not fillable)
        if ($request->filled('new_password')) {
            $user->forceFill([
                'password' => Hash::make($request->input('new_password')),
                'password_changed_at' => now(),
            ])->save();
        }

        if ($photo) {
            $ext = $photo->getClientOriginalExtension();
            $path = "photos/{$member->id}.{$ext}";

            if ($member->photo_path) {
                StorageProvider::delete($member->photo_path);
            }

            StorageProvider::store($path, $photo);
            $member->update(['photo_path' => $path]);
        }

        return redirect()->route('my', ['tab' => 'profile'])->with('success', __('Profile updated.'));
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(ChangeMyPasswordRequest $request): RedirectResponse
    {
        // forceFill: password_changed_at is deliberately NOT in User::$fillable,
        // and update() silently discards non-fillable keys — which left the flag
        // null and trapped members in the set-password redirect loop.
        $request->user()->forceFill([
            'password' => Hash::make($request->input('password')),
            'password_changed_at' => now(),
        ])->save();

        return redirect()->intended(route('my'))->with('success', __('Password changed successfully.'));
    }

    public function storeMealOff(StoreMealOffRequest $request): RedirectResponse
    {
        $member = $request->user()->getMemberOrNull();
        if (! $member) {
            return redirect()->route('my')->with('error', __('Your mess account is not set up.'));
        }

        MealOffRequest::create([
            'mess_id' => Mess::activeId(),
            'member_id' => $member->id,
            'from_date' => $request->validated('from_date'),
            'to_date' => $request->validated('to_date'),
            'reason' => $request->validated('reason'),
            'status' => MealOffStatus::PENDING,
            'requested_at' => now(),
        ]);

        return redirect()->route('my', ['tab' => 'meal-off'])->with('success', __('Meal off request submitted. The manager will review it.'));
    }

    /**
     * Member self-service: save the member's OWN meal entry for TODAY only.
     * The date is pinned server-side; bulkSave re-applies the same guards
     * (closed day, meal off, disabled day) and only writes visible meals.
     */
    public function saveTodayMeal(SaveMyTodayMealRequest $request): RedirectResponse
    {
        $member = $request->user()->getMemberOrNull();
        if (! $member) {
            return redirect()->route('my')->with('error', __('Your mess account is not set up.'));
        }

        $today = Carbon::now(config('app.timezone'));

        if (! $this->canWriteToday($member->id, $today)) {
            return redirect()->route('my', ['tab' => 'meals'])
                ->with('error', __("Today's meal can't be changed (mess closed, month closed, or you are on meal off)."));
        }

        $this->mealGrid->bulkSave($today, [[
            'member_id' => $member->id,
            'breakfast' => $request->boolean('breakfast'),
            'lunch' => $request->boolean('lunch'),
            'dinner' => $request->boolean('dinner'),
        ]]);

        return redirect()->route('my', ['tab' => 'meals'])
            ->with('success', __("Today's meal saved."));
    }

    /**
     * Member self-service: record a guest meal for TODAY, charged to the
     * member's own account. Date and member are pinned server-side.
     */
    public function storeGuestMeal(StoreMyGuestMealRequest $request): RedirectResponse
    {
        $member = $request->user()->getMemberOrNull();
        if (! $member) {
            return redirect()->route('my')->with('error', __('Your mess account is not set up.'));
        }

        $today = Carbon::now(config('app.timezone'));

        if (! $this->canWriteToday($member->id, $today)) {
            return redirect()->route('my', ['tab' => 'meals'])
                ->with('error', __("A guest meal can't be added today (mess closed, month closed, or you are on meal off)."));
        }

        $guestMeal = $this->guestMeals->create([
            'member_id' => $member->id,
            'guest_name' => $request->validated('guest_name'),
            'date' => $today->toDateString(),
            'meal_type' => $request->validated('meal_type'),
            'quantity' => $request->validated('quantity'),
        ]);

        return redirect()->route('my', ['tab' => 'meals'])
            ->with('success', __('Guest meal for :name recorded — :amount will be added to your bill.', [
                'name' => $guestMeal->guest_name,
                'amount' => number_format((float) $guestMeal->charge_amount, 2),
            ]));
    }

    /**
     * Shared guard for member self-service writes: the grid's per-member
     * rules (active, mess open that day, no meal off, day not disabled) plus
     * the month-close ledger freeze (D-19) that the mess routes get from the
     * month.open middleware.
     */
    private function canWriteToday(int $memberId, Carbon $today): bool
    {
        $monthClosed = MonthlyClosing::query()
            ->where('year', $today->year)
            ->where('month', $today->month)
            ->exists();

        return ! $monthClosed && $this->mealGrid->canMemberEdit($today, $memberId);
    }
}
