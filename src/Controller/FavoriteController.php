<?php
 
   namespace App\Controller;

    use App\Entity\User;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Component\HttpFoundation\Response;
    use App\Repository\ProduitRepository;

    class FavoriteController extends AbstractController
    {
        // src/Controller/FavoriteController.php

#[Route('/favorites/toggle/{id}', name: 'toggle_favorite', methods: ['POST'])]

public function toggleFavorite(int $id, EntityManagerInterface $em, ProduitRepository $productRepo): JsonResponse
{
    /** @var User $user */
    $user = $this->getUser();
    $product = $productRepo->find($id);
    
    if (!$product) {
        return $this->json(['success' => false, 'error' => 'Product not found'], 404);
    }
    
    if ($user->hasFavorite($product)) {
        $user->removeFavorite($product);
    } else {
        $user->addFavorite($product);
    }
    
    $em->flush();
    
    return $this->json([
        'success' => true,
        'isFavorite' => $user->hasFavorite($product),
        'count' => $user->getFavorites()->count()
    ]);
}

        #[Route('/favorites', name: 'favorites')]
        
        public function listFavorites(): Response
        {
            /** @var User $user */
            $user = $this->getUser();
            
            return $this->render('favorites/index.html.twig', [
                'favorites' => $user->getFavorites()
            ]);
        }
    }