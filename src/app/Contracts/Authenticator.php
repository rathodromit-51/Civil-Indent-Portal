<?php

namespace App\Contracts;

use App\Models\User;

interface Authenticator
{
    /**
     * Authenticate a user using CPF and password.
     *
     * @param string $cpf
     * @param string $password
     * @return User|null Returns User instance if authenticated, null otherwise.
     */
    public function authenticate(string $cpf, string $password): ?User;
}
