<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/** Identifie les admins principaux (seuls habilités à nommer d’autres admins). */
class PrimaryAdmin
{
    /** @var list<string> */
    private readonly array $emails;

    public function __construct(
        string $email = '',
    ) {
        $emails = [];
        foreach (preg_split('/[\s,;]+/', $email) ?: [] as $part) {
            $normalized = strtolower(trim($part));
            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $normalized;
            }
        }
        $this->emails = array_values(array_unique($emails));
    }

    /**
     * @return list<string>
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    public function isConfigured(): bool
    {
        return $this->emails !== [];
    }

    public function matches(?UserInterface $user): bool
    {
        if (!$this->isConfigured() || !$user instanceof User || !$user->getEmail()) {
            return false;
        }

        return \in_array(strtolower($user->getEmail()), $this->emails, true);
    }

    public function isPrimaryUser(User $user): bool
    {
        return $this->matches($user);
    }
}
