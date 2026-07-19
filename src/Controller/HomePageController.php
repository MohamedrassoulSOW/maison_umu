<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\HeroSettingsRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use App\Service\ProductPersonalizer;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods:['GET'])]
    public function index(
        CategoryRepository $categoryRepository,
        HeroSettingsRepository $heroSettingsRepository,
        ProductPersonalizer $personalizer,
        SessionInterface $session,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {
        $user = $this->getUser();
        $userEntity = $user instanceof User ? $user : null;
        $catalog = $personalizer->catalog($session, $userEntity);

        $products = $paginator->paginate(
            $catalog['products'],
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'hero' => $heroSettingsRepository->getCurrent(),
            'personalized' => $catalog['personalized'],
        ]);
    }

    #[Route('/home/product/{id}/show', name: 'app_home_product_show', methods:['GET'])]
    public function show(
        Product $product,
        CategoryRepository $categoryRepository,
        ProductPersonalizer $personalizer,
        SessionInterface $session,
    ): Response {
        $personalizer->rememberProduct($session, $product, 2);

        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products' => $personalizer->related($product, 5),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods:['GET'])]
    public function filter(
        $id,
        SubCategoryRepository $subCategoryRepository,
        CategoryRepository $categoryRepository,
        ProductPersonalizer $personalizer,
        SessionInterface $session,
    ): Response {
        $subCategory = $subCategoryRepository->find($id);
        if (!$subCategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        $personalizer->rememberSubCategory($session, (int) $id, 3);

        return $this->render('home_page/filter.html.twig', [
            'products' => $subCategory->getProducts(),
            'subCategory' => $subCategory,
            'categories' => $categoryRepository->findAll(),
        ]);
    }
}
