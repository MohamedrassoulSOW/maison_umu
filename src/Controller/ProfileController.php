<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (is_string($plainPassword) && $plainPassword !== '') {
                if (strlen($plainPassword) < 6) {
                    $this->addFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');

                    return $this->render('profile/index.html.twig', [
                        'user' => $user,
                        'profileForm' => $form,
                    ]);
                }

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'profileForm' => $form,
        ]);
    }
}
