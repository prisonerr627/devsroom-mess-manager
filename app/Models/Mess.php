<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['name', 'address', 'monthly_rent', 'manager_contact', 'status', 'join_code', 'created_by'])]
class Mess extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    private static ?int $activeIdCache = null;

    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
        ];
    }

    /**
     * Resolve the active mess id at runtime — the tenant every mess-scoped
     * query is filtered by (see MessScope).
     *
     * Priority:
     *   1. an explicit setActiveId() (queue jobs / console work for one mess)
     *   2. the logged-in user's own mess (users.mess_id) — may be NULL for a
     *      freshly signed-up user who has not joined or created a mess yet;
     *      that null is deliberate and must never fall through to another
     *      mess's data (an unattached super-admin gets the first mess)
     *   3. no authenticated user (console, guest pages): the env override
     *      (mess.active_mess_id) if that Mess exists, else the first Mess row
     *      — the legacy single-mess behaviour
     *
     * Returns null when no mess applies (pre-onboarding, or an unattached user).
     */
    public static function activeId(): ?int
    {
        if (self::$activeIdCache !== null) {
            return self::$activeIdCache;
        }

        $user = auth()->user();
        if ($user !== null) {
            $id = $user->mess_id;

            // An unattached super-admin is the installation owner (the /setup
            // account, created before any mess exists): show them the first
            // mess — legacy single-mess behaviour — instead of the join chooser.
            if ($id === null && $user->hasRole('super-admin')) {
                $id = static::query()->orderBy('id')->value('id');
            }

            // Cache per request only once a user is known, so a scoped query
            // that runs before auth is resolved cannot poison the tenant.
            self::$activeIdCache = $id !== null ? (int) $id : null;

            return self::$activeIdCache;
        }

        // No user (console / guest): env pin, else the first mess. Never
        // consulted for a logged-in user — that would override their tenant.
        $override = config('mess.active_mess_id');
        if (is_int($override) || (is_string($override) && ctype_digit($override))) {
            $id = (int) $override;
            if (static::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $id = static::query()->orderBy('id')->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Pin the active mess for the rest of this process — for queue jobs and
     * console commands, where there is no logged-in user to resolve it from.
     */
    public static function setActiveId(?int $id): void
    {
        self::$activeIdCache = $id;
    }

    public static function forgetActiveIdCache(): void
    {
        self::$activeIdCache = null;
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * A shareable 8-char join code from an unambiguous alphabet (no 0/O/1/I/L),
     * unique across messes.
     */
    public static function generateJoinCode(): string
    {
        do {
            $code = substr(str_replace(['0', 'O', '1', 'I', 'L'], '', strtoupper(Str::random(32))), 0, 8);
        } while (strlen($code) < 8 || static::query()->where('join_code', $code)->exists());

        return $code;
    }

    /**
     * Find an active mess by its join code (case/whitespace-insensitive).
     */
    public static function findByJoinCode(string $code): ?self
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');

        if ($normalized === '') {
            return null;
        }

        return static::query()
            ->where('join_code', $normalized)
            ->where('status', 'active')
            ->first();
    }
}
