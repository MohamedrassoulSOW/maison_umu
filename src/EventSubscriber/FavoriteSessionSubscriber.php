<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Favorite;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Synchronise les favoris DB → session pour l’affichage du cœur actif. */
final class FavoriteSessionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Favorite $favorite,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 4]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler') || str_starts_with($path, '/assets')) {
            return;
        }

        $user = $this->security->getUser();
        $userEntity = $user instanceof User ? $user : null;

        try {
            $this->favorite->getIds($request->getSession(), $userEntity);
        } catch (\Throwable) {
            // Ignore si la table n’est pas encore migrée
        }
    }
}
