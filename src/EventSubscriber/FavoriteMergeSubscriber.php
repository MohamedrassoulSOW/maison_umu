<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Favorite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/** Fusionne les favoris session (invité) vers le compte après connexion. */
final class FavoriteMergeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Favorite $favorite,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User || !$user->getId()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $this->favorite->mergeGuestIntoUser($request->getSession(), $user);
    }
}
