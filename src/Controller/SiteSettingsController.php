<?php

namespace App\Controller;

use App\Form\SiteSettingsType;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/editor/site')]
#[IsGranted('ROLE_EDITOR')]
final class SiteSettingsController extends AbstractController
{
    #[Route('', name: 'app_site_settings', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        SiteSettingsRepository $siteSettingsRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $settings = $siteSettingsRepository->getCurrent();
        $form = $this->createForm(SiteSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $uploadDir = (string) $this->getParameter('brand_logo_dir');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $extension = $logoFile->guessExtension() ?: pathinfo($logoFile->getClientOriginalName(), PATHINFO_EXTENSION);
                $originalName = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFileName = $slugger->slug($originalName).'-'.uniqid().'.'.$extension;

                try {
                    $logoFile->move($uploadDir, $newFileName);

                    $oldPath = $settings->getBrandLogoPath();
                    $settings->setBrandLogoPath('uploads/brand/'.$newFileName);

                    if (str_starts_with($oldPath, 'uploads/brand/')) {
                        $oldFile = rtrim($uploadDir, '/\\').DIRECTORY_SEPARATOR.basename($oldPath);
                        if (is_file($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                } catch (FileException) {
                    $this->addFlash('danger', 'Impossible d’enregistrer le logo.');

                    return $this->render('site/edit.html.twig', [
                        'form' => $form,
                        'settings' => $settings,
                    ]);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Les informations du site ont été mises à jour.');

            return $this->redirectToRoute('app_site_settings');
        }

        return $this->render('site/edit.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }
}
