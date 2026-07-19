<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/** Identifie l’admin principal (seul habilité à nommer d’autres admins). */
class PrimaryAdmin
{
    public function __construct(
        private readonly string $email = '',
    ) {
    }

    public function getEmail(): string
    {
        return strtolower(trim($this->email));
    }

    public function isConfigured(): bool
    {
        return $this->getEmail() !== '' && filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL);
    }

    public function matches(?UserInterface $user): bool
    {
        if (!$this->isConfigured() || !$user instanceof User || !$user->getEmail()) {
            return false;
        }

        return strtolower($user->getEmail()) === $this->getEmail();
    }

    public function isPrimaryUser(User $user): bool
    {
        return $this->matches($user);
    }
}
