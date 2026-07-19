<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FooterSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MenuController extends AbstractController
{
    #[Route('/menu', name: 'app_menu')]
    public function menu(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('leyouts/nav.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/footer', name: 'app_footer')]
    public function footer(
        CategoryRepository $categoryRepository,
        FooterSettingsRepository $footerSettingsRepository,
    ): Response {
        return $this->render('leyouts/footer.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'footerSettings' => $footerSettingsRepository->getCurrent(),
        ]);
    }
}
