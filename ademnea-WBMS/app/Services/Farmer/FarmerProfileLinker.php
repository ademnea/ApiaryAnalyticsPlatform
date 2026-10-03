<?php

namespace App\Services\Farmer;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the `farmers` profile row that backs a login account, or adopts an
 * existing unlinked one.
 *
 * Two paths produce a farmer for the same person — self-registration through
 * the mobile API, and an administrator creating the account directly — and
 * `farmers.email` is UNIQUE. Without adoption, a farmer the admin had already
 * entered into the registry could never self-register: the insert would just
 * collide. Both paths go through here so they cannot drift apart.
 */
class FarmerProfileLinker
{
    /**
     * @param  array{telephone?: string|null, first_name?: string|null, last_name?: string|null, profile_status?: string, status?: string}  $attributes
     */
    public function linkOrCreate(User $user, array $attributes = []): Farmer
    {
        [$firstName, $lastName] = $this->splitName(
            $user->name,
            $attributes['first_name'] ?? null,
            $attributes['last_name'] ?? null
        );

        $telephone = $attributes['telephone'] ?? null;

        $values = array_filter([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'telephone'  => $telephone,
            // The admin registry treats `phone` as canonical; mirror it so a
            // self-registered farmer is searchable the same way.
            'phone'      => $telephone,
        ], fn ($value) => $value !== null && $value !== '');

        $values['status']            = $attributes['status'] ?? 'Inactive';
        $values['profile_status']    = $attributes['profile_status'] ?? 'pending';
        $values['registration_date'] = now();

        $existing = Farmer::whereNull('user_id')
            ->where('email', $user->email)
            ->first();

        if ($existing) {
            // Adopt the registry row. Its status/profile_status are the
            // admin's decision and must not be downgraded by a later
            // self-registration, so they are left as found.
            unset($values['status'], $values['profile_status'], $values['registration_date']);
            $existing->update($values);

            return $existing->refresh();
        }

        return Farmer::create($values);
    }

    /**
     * Registration collects a single `name`, but the profile is stored (and
     * returned by the API) as first/last. Explicit values win when the client
     * sends them; otherwise split on the first space.
     *
     * @return array{0: string, 1: string}
     */
    public function splitName(?string $name, ?string $firstName = null, ?string $lastName = null): array
    {
        if ($firstName !== null && $firstName !== '') {
            return [$firstName, $lastName ?? ''];
        }

        $name = trim((string) $name);

        // No space at all means there is no surname to split off. Str::after()
        // would return the whole string here, duplicating the first name.
        if ($name === '' || ! str_contains($name, ' ')) {
            return [$name, ''];
        }

        return [Str::before($name, ' '), trim(Str::after($name, ' '))];
    }
}
