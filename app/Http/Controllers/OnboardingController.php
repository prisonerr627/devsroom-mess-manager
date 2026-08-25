<?php

namespace App\Http\Controllers;

use App\Http\Requests\Onboarding\CreateMessRequest;
use App\Models\Mess;
use App\Models\Setting;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * "Create a mess": available to any logged-in user who is not attached
     * to a mess yet (fresh signup, or the super-admin on a new install).
     * The creator becomes the mess's super-admin.
     */
    public function create(): View|RedirectResponse
    {
        if (Mess::activeId() !== null) {
            return redirect('/');
        }

        return view('onboarding.create');
    }

    public function store(CreateMessRequest $request): RedirectResponse
    {
        if (Mess::activeId() !== null) {
            return redirect('/');
        }

        $data = $request->validated();
        $user = $request->user();

        $mess = DB::transaction(function () use ($data, $user): Mess {
            $mess = Mess::create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'monthly_rent' => $data['monthly_rent'],
                'manager_contact' => $data['manager_contact'] ?? null,
                'status' => 'active',
                'join_code' => Mess::generateJoinCode(),
                'created_by' => $user->id,
            ]);

            $user->forceFill(['mess_id' => $mess->id])->save();

            // The creator is the super-admin of their mess. Mess data is
            // scoped by users.mess_id, so this grants full control of THIS
            // mess only (see NotificationService for the cross-mess guard).
            if (! $user->hasRole('super-admin')) {
                $user->assignRole(Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']));
            }

            return $mess;
        });

        // Settings below are mess-scoped: make sure the scope sees the new mess.
        Mess::setActiveId($mess->id);

        $settings = [
            ['key' => 'meal_breakfast', 'value' => ['amount' => (float) $data['meal_breakfast']], 'type' => 'number', 'group' => 'meals', 'description' => 'Breakfast meal value'],
            ['key' => 'meal_lunch', 'value' => ['amount' => (float) $data['meal_lunch']], 'type' => 'number', 'group' => 'meals', 'description' => 'Lunch meal value'],
            ['key' => 'meal_dinner', 'value' => ['amount' => (float) $data['meal_dinner']], 'type' => 'number', 'group' => 'meals', 'description' => 'Dinner meal value'],
            ['key' => 'currency', 'value' => ['code' => $data['currency']], 'type' => 'string', 'group' => 'general', 'description' => 'Currency code'],
            ['key' => 'date_format', 'value' => ['format' => $data['date_format']], 'type' => 'string', 'group' => 'general', 'description' => 'Date format'],
            ['key' => 'auto_monthly_close', 'value' => ['enabled' => false], 'type' => 'boolean', 'group' => 'general', 'description' => 'Auto-close month (reserved for v2)'],
        ];

        foreach ($settings as $row) {
            Setting::create(array_merge($row, ['mess_id' => $mess->id]));
        }

        return redirect()->route('home')
            ->with('success', __('Your mess has been created and you are its super admin. Share join code :code with your members so they can join.', ['code' => $mess->join_code]));
    }
}
