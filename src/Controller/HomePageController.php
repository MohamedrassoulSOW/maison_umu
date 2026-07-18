<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\HeroSettingsRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods:['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        HeroSettingsRepository $heroSettingsRepository,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {
        $data = $productRepository->findBy([], ['id' => 'DESC']);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'hero' => $heroSettingsRepository->getCurrent(),
        ]);
    }

    #[Route('/home/product/{id}/show', name: 'app_home_product_show', methods:['GET'])]
    public function show(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $lastProducts = $productRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products' => $lastProducts,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods:['GET'])]
    public function filter($id, SubCategoryRepository $subCategoryRepository, CategoryRepository $categoryRepository): Response
    {
        $subCategory = $subCategoryRepository->find($id);
        if (!$subCategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        return $this->render('home_page/filter.html.twig', [
            'products' => $subCategory->getProducts(),
            'subCategory' => $subCategory,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

}
