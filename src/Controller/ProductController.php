<?php

namespace App\Controller;

use App\Entity\AddProductHistory;
use App\Entity\Product;
use App\Form\AddProductHistoryType;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Repository\AddProductHistoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalName);
                $newFileName = $safeFileName.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('image_dir'),
                        $newFileName
                    );
                    $product->setImage($newFileName);
                } catch (FileException) {
                    $this->addFlash('danger', 'Impossible d’enregistrer l’image du produit.');

                    return $this->render('product/new.html.twig', [
                        'product' => $product,
                        'form' => $form,
                    ]);
                }
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
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalName);
                $newFileName = $safeFileName.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('image_dir'),
                        $newFileName
                    );
                    $product->setImage($newFileName);
                } catch (FileException) {
                    $this->addFlash('danger', 'Impossible d’enregistrer l’image du produit.');

                    return $this->render('product/edit.html.twig', [
                        'product' => $product,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre produit a été modifié avec succè');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $this->addFlash('danger', 'Votre produit a été supprimé avec succès ');
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
        // Récupération du produit
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        // Création du formulaire lié à l'entité AddProductHistory
        $addStock = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qte = $addStock->getQte();

            // Vérification que la quantité est strictement positive
            if ($qte !== null && $qte > 0) {
                // Mise à jour du stock du produit
                $product->setStock($product->getStock() + $qte);

                // Liaison de l'historique au produit
                $addStock->setProduct($product);
                $addStock->setCreatedAt(new \DateTimeImmutable());

                // Persistance en base
                $entityManager->persist($addStock);
                $entityManager->flush();

                $this->addFlash('success', 'Le stock de votre produit a été modifié.');
                return $this->redirectToRoute('app_product_index');
            } else {
                // Quantité invalide (0 ou négative)
                $this->addFlash('danger', 'Le stock ne doit pas être inférieur ou égal à 0.');
            }
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
    ): Response 
    {
        $product = $productRepository->find($id);

        $productAddedHistory = $addProductHistoryRepository->findBy(
            ['product' => $product],
            ['id' => 'DESC'] // tri décroissant
        );

        return $this->render('product/addedStockHistoryShow.html.twig', [
            'productsAdded' => $productAddedHistory,
            'product' => $product,
        ]);
    }

}
