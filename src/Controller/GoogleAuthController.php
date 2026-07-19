<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleOAuthClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthController extends AbstractController
{
    use TargetPathTrait;

    private const SESSION_STATE_KEY = '_google_oauth_state';

    #[Route('/connect/google', name: 'app_connect_google')]
    public function connect(Request $request, GoogleOAuthClient $googleOAuth): RedirectResponse
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home_page');
        }

        if (!$googleOAuth->isConfigured()) {
            $this->addFlash('danger', 'La connexion Google n’est pas encore configurée.');

            return $this->redirectToRoute('app_login');
        }

        $state = $googleOAuth->createState();
        $request->getSession()->set(self::SESSION_STATE_KEY, $state);

        return $this->redirect($googleOAuth->getAuthorizationUrl($state));
    }

    #[Route('/connect/google/check', name: 'app_connect_google_check')]
    public function check(
        Request $request,
        GoogleOAuthClient $googleOAuth,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home_page');
        }

        if (!$googleOAuth->isConfigured()) {
            $this->addFlash('danger', 'La connexion Google n’est pas encore configurée.');

            return $this->redirectToRoute('app_login');
        }

        [$code, $state, $error] = $googleOAuth->extractCodeAndState($request);
        $expectedState = (string) $request->getSession()->remove(self::SESSION_STATE_KEY);

        if ($error !== '') {
            $this->addFlash('danger', 'Connexion Google annulée.');

            return $this->redirectToRoute('app_login');
        }

        if ($code === '' || $state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->addFlash('danger', 'Session Google invalide. Réessayez.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $googleUser = $googleOAuth->fetchUser($code);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de finaliser la connexion Google.');

            return $this->redirectToRoute('app_login');
        }

        $googleId = (string) $googleUser['sub'];
        $email = strtolower(trim((string) $googleUser['email']));

        $user = $userRepository->findOneBy(['googleId' => $googleId])
            ?? $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(null);
            $entityManager->persist($user);
        }

        $user->setGoogleId($googleId);
        $user->setFirstName($googleUser['given_name'] ?? $user->getFirstName() ?? 'Client');
        $user->setLastName($googleUser['family_name'] ?? $user->getLastName() ?? 'UMU');

        $entityManager->flush();

        $security->login($user, firewallName: 'main');

        if ($targetPath = $this->getTargetPath($request->getSession(), 'main')) {
            return $this->redirect($targetPath);
        }

        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_EDITOR', $roles, true)) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->redirectToRoute('app_home_page');
    }
}
