<?php

namespace App\Controller;

use App\Entity\AddProductHistory;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\AddProductHistoryType;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Repository\AddProductHistoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor/product')]
#[IsGranted('ROLE_EDITOR')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('images')->getData() ?: [];

            try {
                $imageUploader->addFiles($product, \is_array($files) ? $files : [$files]);
            } catch (FileException) {
                $this->addFlash('danger', 'Impossible d’enregistrer les images du produit.');

                return $this->render('product/new.html.twig', [
                    'product' => $product,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new AddProductHistory();
            $stockHistory->setQte($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Votre produit a été ajouté.');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('images')->getData() ?: [];

            try {
                $imageUploader->addFiles($product, \is_array($files) ? $files : [$files]);
            } catch (FileException) {
                $this->addFlash('danger', 'Impossible d’enregistrer les images du produit.');

                return $this->render('product/edit.html.twig', [
                    'product' => $product,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre produit a été modifié avec succès.');

            return $this->redirectToRoute('app_product_edit', ['id' => $product->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/image/{imageId}/delete', name: 'app_product_image_delete', methods: ['POST'])]
    public function deleteImage(
        Request $request,
        Product $product,
        int $imageId,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_product_image'.$imageId, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $image = null;
        foreach ($product->getImages() as $item) {
            if ($item->getId() === $imageId) {
                $image = $item;
                break;
            }
        }

        if (!$image instanceof ProductImage) {
            throw $this->createNotFoundException('Image introuvable.');
        }

        $imageUploader->removeImage($product, $image);
        $entityManager->flush();
        $this->addFlash('success', 'Image supprimée.');

        return $this->redirectToRoute('app_product_edit', ['id' => $product->getId()]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductImageUploader $imageUploader,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $imageUploader->removeAll($product);
            $entityManager->remove($product);
            $this->addFlash('danger', 'Votre produit a été supprimé avec succès.');
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['POST', 'GET'])]
    public function addStock(
        int $id,
        EntityManagerInterface $entityManager,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $addStock = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qte = $addStock->getQte();

            if ($qte !== null && $qte > 0) {
                $product->setStock($product->getStock() + $qte);
                $addStock->setProduct($product);
                $addStock->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($addStock);
                $entityManager->flush();

                $this->addFlash('success', 'Le stock de votre produit a été modifié.');
                return $this->redirectToRoute('app_product_index');
            }

            $this->addFlash('danger', 'Le stock ne doit pas être inférieur ou égal à 0.');
        }

        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function productAddHistory(
        $id,
        ProductRepository $productRepository,
        AddProductHistoryRepository $addProductHistoryRepository
    ): Response {
        $product = $productRepository->find($id);

        $productAddedHistory = $addProductHistoryRepository->findBy(
            ['product' => $product],
            ['id' => 'DESC']
        );

        return $this->render('product/addedStockHistoryShow.html.twig', [
            'productsAdded' => $productAddedHistory,
            'product' => $product,
        ]);
    }
}
