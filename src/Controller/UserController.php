<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor', methods: ['POST'])]
    public function changeRole(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_editor'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a été promu au rôle d\'éditeur avec succès.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role', methods: ['POST'])]
    public function editorRoleRemove(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_remove_editor'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a été retiré de son rôle d\'éditeur avec succès.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove', name: 'app_user_remove', methods: ['POST'])]
    public function userRemove(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if (!$this->isCsrfTokenValid('user_delete'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($this->getUser() && $this->getUser()->getUserIdentifier() === $user->getUserIdentifier()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_user');
        }

        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');

        return $this->redirectToRoute('app_user');
    }
}
