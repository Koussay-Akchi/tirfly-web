<?php

namespace App\Controller;

use App\Entity\{Article, Panier, Produit, Client};
use App\Repository\{PanierRepository, ProduitRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class PanierController extends AbstractController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private PanierRepository $panierRepo,
        private ProduitRepository $produitRepo
    ) {}

    #[Route('/panier', name: 'panier')]
    public function index(): Response
    {
        $user = $this->getUserOrRedirect();
        $panier = $this->getOrCreateActivePanier($user);
        
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'articles' => $panier->getArticles(),
            'total' => $this->calculatePanierTotal($panier)
        ]);
    }

    #[Route('/panier/ajouter/{produitId}', name: 'ajouter_article', methods: ['POST'])]
    public function ajouterArticle(int $produitId, Request $request): Response
    {
        $user = $this->getUserOrRedirect();
        $produit = $this->produitRepo->find($produitId) ?? $this->redirectWithError('Produit non trouvé');
        
        $panier = $this->getOrCreateActivePanier($user);
        $quantite = $request->request->getInt('quantite', 1);

        $this->handleArticleAddition($panier, $produit, $quantite);
        
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/modifier/{id}', name: 'modifier_quantite_article', methods: ['POST'])]
    public function modifierQuantite(Article $article, Request $request): Response
    {
        $this->validateUserOwnership($article->getPanier());
        
        $quantite = $request->request->getInt('quantite');
        $produit = $article->getProduit();

        if ($quantite <= 0) {
            return $this->redirectToRoute('supprimer_article', ['id' => $article->getId()]);
        }

        $this->updateArticleQuantity($article, $quantite, $produit);
        
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'supprimer_article', methods: ['POST'])]
    public function supprimerArticle(Article $article): Response
    {
        $panier = $this->validateUserOwnership($article->getPanier());
        
        $this->em->remove($article);
        $this->em->flush();
        $this->calculatePanierTotal($panier);

        $this->addFlash('success', 'Produit retiré du panier');
        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/vider', name: 'vider_panier', methods: ['POST'])]
    public function viderPanier(): Response
    {
        $panier = $this->getOrCreateActivePanier($this->getUserOrRedirect());
        
        foreach ($panier->getArticles() as $article) {
            $this->em->remove($article);
        }
        
        $panier->setPrixTotal(0);
        $this->em->flush();

        $this->addFlash('success', 'Panier vidé');
        return $this->redirectToRoute('panier');
    }

    // Helper methods
    private function getOrCreateActivePanier(Client $client): Panier
    {
        return $this->panierRepo->findActivePanierForUser($client) ?? $this->createNewPanier($client);
    }

    private function createNewPanier(Client $client): Panier
    {
        $panier = (new Panier())
            ->setClient($client)
            ->setEtat('active')
            ->setPrixTotal(0);
        
        $this->em->persist($panier);
        $this->em->flush();
        
        return $panier;
    }

    private function calculatePanierTotal(Panier $panier): float
    {
        $total = array_reduce(
            $panier->getArticles()->toArray(),
            fn(float $sum, Article $article) => $sum + $article->getTotal(),
            0
        );
        
        $panier->setPrixTotal($total);
        $this->em->persist($panier);
        $this->em->flush();

        return $total;
    }

    private function handleArticleAddition(Panier $panier, Produit $produit, int $quantite): void
    {
        $existingArticle = $this->findExistingArticle($panier, $produit->getId());

        if ($existingArticle) {
            $this->updateExistingArticle($existingArticle, $quantite, $produit);
        } else {
            $this->createNewArticle($panier, $produit, $quantite);
        }

        $this->em->flush();
        $this->addFlash('success', 'Produit ajouté au panier');
    }

    private function findExistingArticle(Panier $panier, int $produitId): ?Article
    {
        foreach ($panier->getArticles() as $article) {
            if ($article->getProduit()->getId() === $produitId) {
                return $article;
            }
        }
        return null;
    }

    private function updateExistingArticle(Article $article, int $quantite, Produit $produit): void
    {
        $newQuantity = $article->getQuantite() + $quantite;
        $this->validateStock($produit, $newQuantity);
        
        $article->setQuantite($newQuantity)
                ->setTotal($newQuantity * $produit->getPrixUnitaire());
    }

    private function createNewArticle(Panier $panier, Produit $produit, int $quantite): void
    {
        $this->validateStock($produit, $quantite);
        
        $article = (new Article())
            ->setProduit($produit)
            ->setQuantite($quantite)
            ->setTotal($quantite * $produit->getPrixUnitaire())
            ->setPanier($panier);
        
        $panier->addArticle($article);
        $this->em->persist($article);
    }

    private function updateArticleQuantity(Article $article, int $quantite, Produit $produit): void
    {
        $this->validateStock($produit, $quantite);
        
        $article->setQuantite($quantite)
                ->setTotal($quantite * $produit->getPrixUnitaire());
        
        $this->em->flush();
        $this->calculatePanierTotal($article->getPanier());
        $this->addFlash('success', 'Quantité mise à jour');
    }

    private function validateStock(Produit $produit, int $quantite): void
    {
        if ($quantite > $produit->getQuantiteStock()) {
            throw $this->createAccessDeniedException('Quantité demandée dépasse le stock disponible');
        }
    }

    private function getUserOrRedirect(): Client
    {
        $user = $this->security->getUser();
        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException('Vous devez être connecté');
        }
        return $user;
    }

    private function validateUserOwnership(Panier $panier): Panier
    {
        $user = $this->getUserOrRedirect();
        if ($panier->getClient() !== $user) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce panier');
        }
        return $panier;
    }

    private function redirectWithError(string $message): Response
    {
        $this->addFlash('error', $message);
        return $this->redirectToRoute('liste_produits');
    }
    // src/Controller/PanierController.php

#[Route('/checkout', name: 'checkout')]
public function checkout(): Response
{
    $user = $this->getUserOrRedirect();
    $panier = $this->getOrCreateActivePanier($user);

    if ($panier->getArticles()->isEmpty()) {
        $this->addFlash('warning', 'Votre panier est vide');
        return $this->redirectToRoute('panier');
    }

    return $this->render('panier/checkout.html.twig', [
        'panier' => $panier,
        'total' => $this->calculatePanierTotal($panier)
    ]);
}
}