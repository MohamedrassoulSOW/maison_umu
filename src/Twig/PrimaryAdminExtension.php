<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\PrimaryAdmin;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PrimaryAdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly PrimaryAdmin $primaryAdmin,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_primary_admin', [$this, 'isPrimaryAdmin']),
            new TwigFunction('is_primary_admin_user', [$this, 'isPrimaryAdminUser']),
        ];
    }

    public function isPrimaryAdmin(?UserInterface $user): bool
    {
        return $this->primaryAdmin->matches($user);
    }

    public function isPrimaryAdminUser(User $user): bool
    {
        return $this->primaryAdmin->isPrimaryUser($user);
    }
}
