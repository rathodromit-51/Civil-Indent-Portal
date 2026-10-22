<?php

namespace App\Services\Auth;

use App\Contracts\Authenticator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DevAuthenticator implements Authenticator
{
    public function __construct(
        protected string $devPassword = 'secret123'
    ) {}

    /**
     * Authenticate a user in dev mode.
     * Any seeded active user with a matching CPF logs in with the fixed dev password,
     * or alternatively their hashed database password.
     */
    public function authenticate(string $cpf, string $password): ?User
    {
        $cpf = trim($cpf);
        $user = User::where('cpf', $cpf)->first();

        if (! $user) {
            return null;
        }

        // Only active users can log in
        if (! $user->active) {
            return null;
        }

        // Check if matching fixed dev password or real hashed password
        if ($password === $this->devPassword || Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }
}
