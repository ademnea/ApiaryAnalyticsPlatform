<?php

namespace App\Http\Requests\Api\Farmer\Concerns;

/**
 * One definition of what a farmer telephone number looks like, shared by
 * registration and profile editing so the two cannot drift apart.
 *
 * Deliberately permissive: the deployment spans Uganda, South Sudan and
 * Tanzania, numbers are entered by hand, and rejecting a valid local format
 * is worse here than accepting a loosely formatted one.
 */
trait HasTelephoneRule
{
    /**
     * @return array<int, string>
     */
    protected function telephoneRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:/^\+?[0-9][0-9\s\-]{6,19}$/',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function telephoneMessages(): array
    {
        return [
            'telephone.regex' => 'Please enter a valid telephone number, for example +256700000000.',
        ];
    }
}
