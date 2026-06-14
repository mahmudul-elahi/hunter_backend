<?php

namespace App\Services;

use App\Exceptions\SocialAuthException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthService
{
    /**
     * Verify a provider token, resolve (or create) the matching user, and
     * return the authenticated account.
     *
     * @throws SocialAuthException
     */
    public function authenticate(string $provider, string $token): User
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (Throwable) {
            throw SocialAuthException::invalidToken();
        }

        $user = DB::transaction(fn (): User => $this->resolveUser($provider, $socialUser));

        if (! $user->is_active) {
            throw SocialAuthException::deactivated();
        }

        return $user;
    }

    private function resolveUser(string $provider, SocialiteUser $socialUser): User
    {
        $idField = "{$provider}_id";
        $email = $socialUser->getEmail();

        $user = User::where($idField, $socialUser->getId())
            ->when($email, fn ($query, $email) => $query->orWhere('email', $email))
            ->first();

        if ($user) {
            if (! $user->{$idField}) {
                $user->update([$idField => $socialUser->getId()]);
            }

            return $user;
        }

        [$firstName, $lastName] = $this->resolveName($socialUser->getName(), $email);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => now(),
            $idField => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        $user->assignRole('user');

        return $user;
    }

    /**
     * Resolve a first/last name, falling back to the email when the provider
     * sends no name (e.g. Apple, whose identity token never carries one).
     *
     * @return array{0: string, 1: string}
     */
    private function resolveName(?string $fullName, ?string $email): array
    {
        if ($fullName !== null && trim($fullName) !== '') {
            $parts = explode(' ', trim($fullName), 2);

            return [$parts[0], $parts[1] ?? ''];
        }

        return $this->nameFromEmail($email);
    }

    /**
     * Derive a best-effort name from the email local part:
     * `john.doe@example.com` becomes `['John', 'Doe']`.
     *
     * @return array{0: string, 1: string}
     */
    private function nameFromEmail(?string $email): array
    {
        $localPart = $email ? Str::before($email, '@') : '';
        $segments = preg_split('/[._+\-]+/', $localPart, 2, PREG_SPLIT_NO_EMPTY) ?: [];

        $firstName = Str::title($segments[0] ?? '');
        $lastName = Str::title($segments[1] ?? '');

        return [$firstName !== '' ? $firstName : 'User', $lastName];
    }
}
