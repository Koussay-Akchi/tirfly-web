<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Article;
use App\Entity\Panier;
use App\Repository\ProduitRepository;
use App\Repository\PanierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProduitType;
use Knp\Snappy\Pdf;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\StockMonitor;
use App\Service\TwilioSmsService;
use Psr\Log\LoggerInterface;

class ProduitController extends AbstractController
{
    #[Route('/produits', name: 'liste_produits')]
    public function index(ProduitRepository $produitRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category');
        $ecoFriendly = $request->query->get('ecoFriendly');

        $produits = $produitRepository->filterProduits($search, $category, $ecoFriendly);
        $categories = $produitRepository->findDistinctCategories();

        return $this->render('produits/liste-produits.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'searchParams' => [
                'search' => $search,
                'category' => $category,
                'ecoFriendly' => $ecoFriendly
            ]
        ]);
    }

    #[Route('/produit/{id}', name: 'details_produit', requirements: ['id' => '\d+'])]
    public function details(Produit $produit): Response
    {
        return $this->render('produits/details-produit.html.twig', [
            'produit' => $produit
        ]);
    }

    #[Route('/admin/produits/ajout', name: 'ajout_produit')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload for BLOB storage
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                try {
                    $imageContent = file_get_contents($imageFile->getPathname());
                    $produit->setImage($imageContent);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('ajout_produit');
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit ajouté avec succès.');
            return $this->redirectToRoute('admin_liste_produits');
        }

        return $this->render('produits/ajout-produit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/produits', name: 'admin_liste_produits')]
public function adminProduits(ProduitRepository $produitRepository): Response
{
    $produits = $produitRepository->findAllOrderedByName();
    $categories = $produitRepository->getCategoriesWithCounts();
    $ecoCount = $produitRepository->count(['ecoFriendly' => true]);
    $lowStockCount = $produitRepository->countLowStockProducts(10);

    return $this->render('produits/tableau-produits.html.twig', [
        'produits' => $produits,
        'categories' => $categories,
        'ecoCount' => $ecoCount,
        'lowStockCount' => $lowStockCount
    ]);
}

#[Route('/test-twilio')]
public function testTwilio(TwilioSmsService $twilio): Response
{
    $result = $twilio->sendLowStockAlert(
        'TEST PRODUCT',
        '+21658911032' // Your number
    );
    return new Response($result ? 'Sent!' : 'Failed - check logs');
}
#[Route('/admin/produits/edit/{id}', name: 'edit_produit', requirements: ['id' => '\d+'])]
public function edit(
    Produit $produit, 
    Request $request, 
    EntityManagerInterface $entityManager,
    StockMonitor $stockMonitor,
    LoggerInterface $logger
): Response {
    $form = $this->createForm(ProduitType::class, $produit);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Handle image upload
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            try {
                $produit->setImage(file_get_contents($imageFile->getPathname()));
            } catch (FileException $e) {
                $logger->error('Image upload failed', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                return $this->redirectToRoute('edit_produit', ['id' => $produit->getId()]);
            }
        }

        // Update and save changes
        $entityManager->flush();
        
        // Check for low stock after saving
        $stockMonitor->checkLowStock($produit);

        $this->addFlash('success', 'Le produit a été mis à jour avec succès.');
        return $this->redirectToRoute('admin_liste_produits');
    }

    return $this->render('produits/edit-produit.html.twig', [
        'form' => $form->createView(),
        'produit' => $produit
    ]);
}

    #[Route('/admin/produits/delete/{id}', name: 'delete_produit', methods: ['POST', 'GET'])]
public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
{
    if ($request->isMethod('GET')) {
        return $this->render('admin/confirm_delete.html.twig', [
            'produit' => $produit,
        ]);
    }

    if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
        $entityManager->remove($produit);
        $entityManager->flush();
        $this->addFlash('success', 'Produit supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }

    return $this->redirectToRoute('admin_liste_produits');
}

    #[Route('/panier/ajouter-article/{produitId}', name: 'ajouter_article', requirements: ['produitId' => '\d+'], methods: ['POST'])]
    public function addArticle(int $produitId, Request $request, ProduitRepository $produitRepository, PanierRepository $panierRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $produit = $produitRepository->find($produitId);
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        $quantite = (int) $request->request->get('quantite', 1);
        
        if ($quantite <= 0 || $quantite > $produit->getQuantiteStock()) {
            $this->addFlash('error', 'Quantité invalide ou stock insuffisant');
            return $this->redirectToRoute('details_produit', ['id' => $produitId]);
        }

        $user = $this->getUser();
        $panier = $panierRepository->findActivePanierForUser($user);

        if (!$panier) {
            $panier = new Panier();
            $panier->setClient($user)
                   ->setPrixTotal(0)
                   ->setEtat('en cours');
            $entityManager->persist($panier);
        }

        // Check if product already exists in cart
        $existingArticle = null;
        foreach ($panier->getArticles() as $article) {
            if ($article->getProduit()->getId() === $produit->getId()) {
                $existingArticle = $article;
                break;
            }
        }

        if ($existingArticle) {
            $newQuantity = $existingArticle->getQuantite() + $quantite;
            if ($newQuantity > $produit->getQuantiteStock()) {
                $this->addFlash('error', 'Quantité totale dépasse le stock disponible');
                return $this->redirectToRoute('details_produit', ['id' => $produitId]);
            }
            $existingArticle->setQuantite($newQuantity);
            $existingArticle->setTotal($newQuantity * $produit->getPrixUnitaire());
        } else {
            $article = new Article();
            $article->setProduit($produit)
                    ->setQuantite($quantite)
                    ->setTotal($quantite * $produit->getPrixUnitaire())
                    ->setPanier($panier);
            $entityManager->persist($article);
        }

        // Update cart total
        $total = 0;
        foreach ($panier->getArticles() as $article) {
            $total += $article->getTotal();
        }
        $panier->setPrixTotal($total);

        $entityManager->flush();
        $this->addFlash('success', 'Produit ajouté au panier');
        return $this->redirectToRoute('details_produit', ['id' => $produitId]);
    }

    #[Route('/produit/image/{id}', name: 'produit_image', requirements: ['id' => '\d+'])]
    public function getImage(int $id, ProduitRepository $produitRepository): Response
    {
        $produit = $produitRepository->find($id);
        if (!$produit || !$produit->getImage()) {
            throw $this->createNotFoundException('Image non trouvée');
        }

        $imageContent = stream_get_contents($produit->getImage());

        return new StreamedResponse(function() use ($imageContent) {
            echo $imageContent;
        }, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
            'ETag' => md5($imageContent)
        ]);
    }
    #[Route('/admin/produits/export-pdf', name: 'export_products_pdf')]
public function exportPdf(Pdf $knpSnappyPdf, ProduitRepository $produitRepository): Response
{
    $produits = $produitRepository->findAllOrderedByName();
    $categories = $produitRepository->getCategoriesWithCounts();

    $html = $this->renderView('produits/export_pdf.html.twig', [
        'produits' => $produits,
        'categories' => $categories,
        'date' => new \DateTime()
    ]);

    // Generate unique filename
    $filename = 'products_export_'.date('Y-m-d').'.pdf';

    return new Response(
        $knpSnappyPdf->getOutputFromHtml($html),
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ]
    );
}
}