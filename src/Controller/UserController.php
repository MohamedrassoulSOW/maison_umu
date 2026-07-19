<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PrimaryAdmin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly PrimaryAdmin $primaryAdmin,
    ) {
    }

    #[Route('/admin/user', name: 'app_user', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy([], ['id' => 'ASC']),
            'primaryAdminConfigured' => $this->primaryAdmin->isConfigured(),
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor', methods: ['POST'])]
    public function changeRole(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_editor'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->denyProtectedTarget($user, 'promouvoir')) {
            return $this->redirectToRoute('app_user');
        }

        if ($user->isBlocked()) {
            $this->addFlash('danger', 'Débloquez d’abord cet utilisateur.');

            return $this->redirectToRoute('app_user');
        }

        $user->setRoles(['ROLE_EDITOR']);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur promu éditeur.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role', methods: ['POST'])]
    public function editorRoleRemove(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_remove_editor'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->denyProtectedTarget($user, 'modifier')) {
            return $this->redirectToRoute('app_user');
        }

        if ($user->isAdmin()) {
            $this->addFlash('danger', 'Retirez d’abord le rôle administrateur.');

            return $this->redirectToRoute('app_user');
        }

        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();
        $this->addFlash('success', 'Rôle éditeur retiré.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/to/admin', name: 'app_user_to_admin', methods: ['POST'])]
    public function toAdmin(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_admin'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$this->primaryAdmin->matches($this->getUser())) {
            $this->addFlash('danger', 'Seul l’admin principal peut nommer un administrateur.');

            return $this->redirectToRoute('app_user');
        }

        if ($this->primaryAdmin->isPrimaryUser($user)) {
            $this->addFlash('danger', 'Cet utilisateur est déjà l’admin principal.');

            return $this->redirectToRoute('app_user');
        }

        if ($user->isBlocked()) {
            $this->addFlash('danger', 'Débloquez d’abord cet utilisateur.');

            return $this->redirectToRoute('app_user');
        }

        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur promu administrateur.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/admin/role', name: 'app_user_remove_admin_role', methods: ['POST'])]
    public function removeAdminRole(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_remove_admin'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$this->primaryAdmin->matches($this->getUser())) {
            $this->addFlash('danger', 'Seul l’admin principal peut retirer un rôle administrateur.');

            return $this->redirectToRoute('app_user');
        }

        if ($this->primaryAdmin->isPrimaryUser($user)) {
            $this->addFlash('danger', 'Le rôle de l’admin principal ne peut pas être retiré.');

            return $this->redirectToRoute('app_user');
        }

        $user->setRoles(['ROLE_EDITOR']);
        $entityManager->flush();
        $this->addFlash('success', 'Rôle administrateur retiré (reste éditeur).');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/block', name: 'app_user_block', methods: ['POST'])]
    public function block(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_block'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->isSelf($user)) {
            $this->addFlash('danger', 'Vous ne pouvez pas bloquer votre propre compte.');

            return $this->redirectToRoute('app_user');
        }

        if ($this->primaryAdmin->isPrimaryUser($user)) {
            $this->addFlash('danger', 'L’admin principal ne peut pas être bloqué.');

            return $this->redirectToRoute('app_user');
        }

        if ($user->isAdmin() && !$this->primaryAdmin->matches($this->getUser())) {
            $this->addFlash('danger', 'Seul l’admin principal peut bloquer un administrateur.');

            return $this->redirectToRoute('app_user');
        }

        $user->setIsBlocked(true);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur bloqué.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/unblock', name: 'app_user_unblock', methods: ['POST'])]
    public function unblock(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_unblock'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setIsBlocked(false);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur débloqué.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove', name: 'app_user_remove', methods: ['POST'])]
    public function userRemove(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_delete'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->isSelf($user)) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_user');
        }

        if ($this->primaryAdmin->isPrimaryUser($user)) {
            $this->addFlash('danger', 'L’admin principal ne peut pas être supprimé.');

            return $this->redirectToRoute('app_user');
        }

        if ($user->isAdmin() && !$this->primaryAdmin->matches($this->getUser())) {
            $this->addFlash('danger', 'Seul l’admin principal peut supprimer un administrateur.');

            return $this->redirectToRoute('app_user');
        }

        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirectToRoute('app_user');
    }

    private function isSelf(User $user): bool
    {
        $current = $this->getUser();

        return $current instanceof User
            && $current->getUserIdentifier() === $user->getUserIdentifier();
    }

    private function denyProtectedTarget(User $user, string $action): bool
    {
        if ($this->primaryAdmin->isPrimaryUser($user)) {
            $this->addFlash('danger', sprintf('Impossible de %s l’admin principal.', $action));

            return true;
        }

        return false;
    }
}
