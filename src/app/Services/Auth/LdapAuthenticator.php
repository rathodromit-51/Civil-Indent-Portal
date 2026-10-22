<?php

namespace App\Services\Auth;

use App\Contracts\Authenticator;
use App\Models\User;
use RuntimeException;

class LdapAuthenticator implements Authenticator
{
    /**
     * Authenticate a user via LDAP directory.
     * Stub implementation for now.
     */
    public function authenticate(string $cpf, string $password): ?User
    {
        throw new RuntimeException('LDAP authentication is not implemented yet.');
    }
}
