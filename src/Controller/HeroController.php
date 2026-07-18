<?php

namespace App\Controller;

use App\Form\HeroSettingsType;
use App\Repository\HeroSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/editor/hero')]
#[IsGranted('ROLE_EDITOR')]
final class HeroController extends AbstractController
{
    #[Route('', name: 'app_hero_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        HeroSettingsRepository $heroSettingsRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $hero = $heroSettingsRepository->getCurrent();
        $form = $this->createForm(HeroSettingsType::class, $hero);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalName);
                $newFileName = $safeFileName.'-'.uniqid().'.'.$imageFile->guessExtension();
                $uploadDir = $this->getParameter('hero_image_dir');

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                try {
                    $imageFile->move($uploadDir, $newFileName);

                    $oldImage = $hero->getImage();
                    $hero->setImage($newFileName);

                    if ($oldImage) {
                        $oldPath = rtrim($uploadDir, '/\\').DIRECTORY_SEPARATOR.$oldImage;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                } catch (FileException) {
                    $this->addFlash('danger', 'Impossible d’enregistrer l’image du hero.');

                    return $this->render('hero/edit.html.twig', [
                        'hero' => $hero,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Le hero a été mis à jour. Vérifiez la page d’accueil.');

            return $this->redirectToRoute('app_hero_edit');
        }

        return $this->render('hero/edit.html.twig', [
            'hero' => $hero,
            'form' => $form,
        ]);
    }

    #[Route('/remove-image', name: 'app_hero_remove_image', methods: ['POST'])]
    public function removeImage(
        Request $request,
        HeroSettingsRepository $heroSettingsRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $hero = $heroSettingsRepository->getCurrent();

        if (!$this->isCsrfTokenValid('hero_remove_image', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($hero->getImage()) {
            $path = rtrim((string) $this->getParameter('hero_image_dir'), '/\\').DIRECTORY_SEPARATOR.$hero->getImage();
            if (is_file($path)) {
                @unlink($path);
            }
            $hero->setImage(null);
            $entityManager->flush();
            $this->addFlash('success', 'Image du hero supprimée.');
        }

        return $this->redirectToRoute('app_hero_edit');
    }
}
