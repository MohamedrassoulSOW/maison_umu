<?php

namespace App\Controller;

use App\Form\FooterSettingsType;
use App\Repository\FooterSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor/footer')]
#[IsGranted('ROLE_EDITOR')]
final class FooterSettingsController extends AbstractController
{
    #[Route('', name: 'app_footer_settings', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        FooterSettingsRepository $footerSettingsRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $settings = $footerSettingsRepository->getCurrent();
        $form = $this->createForm(FooterSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Footer mis à jour.');

            return $this->redirectToRoute('app_footer_settings');
        }

        return $this->render('footer/edit.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }
}
