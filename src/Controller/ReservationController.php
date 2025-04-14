<?php

namespace App\Controller;

use App\Entity\{Reservation, Sejour, Voyage, Hebergement, Pack};
use App\Repository\{
    ClientRepository,
    FoyerRepository,
    HotelRepository,
    LogementRepository,
    ReservationRepository,
    VoyageRepository,
    HebergementRepository,
    PackRepository
};
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    #[Route('/reservations', name: 'historique_reservations')]
    public function historiqueReservations(
        Request $request,
        ReservationRepository $reservationRepo,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUserOrRedirect();
        
        $query = $reservationRepo->createQueryBuilder('r')
            ->where('r.client = :user')
            ->setParameter('user', $user)
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('reservations/historique-reservations.html.twig', [
            'reservations' => $pagination,
            'reservationsAll' => $query->getResult(),
        ]);
    }

    #[Route('/admin/reservations', name: 'admin_liste_reservations')]
    public function adminReservations(
        Request $request,
        ReservationRepository $reservationRepo,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $query = $reservationRepo->createQueryBuilder('r')->getQuery();
        
        return $this->render('reservations/tableau-reservations.html.twig', [
            'reservations' => $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10
            ),
            'reservationsAll' => $reservationRepo->findAll(),
        ]);
    }

    #[Route('/reservations/new/{type}/{id}', name: 'ajout_reservation')]
    public function new(
        string $type,
        int $id,
        Request $request,
        VoyageRepository $voyageRepo,
        HebergementRepository $hebergementRepo,
        PackRepository $packRepo,
        ClientRepository $clientRepo,
        HotelRepository $hotelRepo,
        FoyerRepository $foyerRepo,
        LogementRepository $logementRepo
    ): Response {
        $client = $this->getUserOrRedirect();
        $entity = $this->getReservationEntity($type, $id, $voyageRepo, $hebergementRepo, $packRepo);
        $price = $this->calculatePrice($type, $entity, $hotelRepo, $foyerRepo, $logementRepo);

        if ($request->isMethod('POST')) {
            $reservation = $this->createReservation(
                $type,
                $entity,
                $client,
                $request,
                $price
            );
            
            $this->em->persist($reservation);
            $this->em->flush();
            
            $this->addFlash('success', 'Réservation confirmée !');
            return $this->redirectToRoute('historique_reservations');
        }

        return $this->render('reservations/form-reservation.html.twig', [
            'type' => $type,
            'selected' => $entity,
            'formData' => $this->getDefaultFormData(),
            'selectedPrice' => $price,
        ]);
    }

    // Helper methods
    private function getUserOrRedirect()
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedException("Vous devez être connecté pour accéder à cette page.");
        }
        return $user;
    }

    private function getReservationEntity(
        string $type,
        int $id,
        VoyageRepository $voyageRepo,
        HebergementRepository $hebergementRepo,
        PackRepository $packRepo
    ) {
        return match ($type) {
            'voyage' => $voyageRepo->find($id),
            'hebergement' => $hebergementRepo->find($id),
            'pack' => $packRepo->find($id),
            default => throw $this->createNotFoundException("Type de réservation non supporté."),
        } ?? throw $this->createNotFoundException("L'élément sélectionné est introuvable.");
    }

    private function calculatePrice(
        string $type,
        $entity,
        HotelRepository $hotelRepo,
        FoyerRepository $foyerRepo,
        LogementRepository $logementRepo
    ): float {
        if ($type === 'hebergement') {
            $priceEntity = $hotelRepo->find($entity->getId()) 
                ?? $logementRepo->find($entity->getId())
                ?? $foyerRepo->find($entity->getId());
                
            return $priceEntity?->getPrix() ?? $priceEntity?->getFrais() ?? 0.0;
        }
        
        return $entity->getPrix();
    }

    private function getDefaultFormData(): array
    {
        return [
            'personCount' => 1,
            'jours' => 1,
            'reservationDate' => (new \DateTime())->format('Y-m-d'),
            'coupon' => null,
        ];
    }

    private function createReservation(
        string $type,
        $entity,
        $client,
        Request $request,
        float $basePrice
    ): Reservation {
        $reservation = new Reservation();
        $reservation->setClient($client)
            ->setNombrePersonnes((int)$request->request->get('personCount', 1))
            ->setStatut('EN_ATTENTE');

        if ($type === 'hebergement') {
            $sejour = (new Sejour())
                ->setHebergement($entity)
                ->setJours((int)$request->request->get('jours', 1));
            
            $this->em->persist($sejour);
            
            $reservation->setSejour($sejour)
                ->setDateReservation(new \DateTime($request->request->get('reservationDate')))
                ->setPrixTotal($this->calculateTotalPrice(
                    $basePrice,
                    $reservation->getNombrePersonnes(),
                    $sejour->getJours(),
                    $request->request->get('coupon')
                ));
        } else {
            $reservation->{'set'.ucfirst($type)}($entity)
                ->setDateReservation(new \DateTime())
                ->setPrixTotal($this->calculateTotalPrice(
                    $basePrice,
                    $reservation->getNombrePersonnes(),
                    1,
                    $request->request->get('coupon')
                ));
        }

        return $reservation;
    }

    private function calculateTotalPrice(
        float $basePrice,
        int $personCount,
        int $days,
        ?string $couponCode
    ): float {
        $total = $basePrice * $personCount * $days;
        
        if ($couponCode) {
            $total *= 0.9; // 10% discount
        }
        
        return $total;
    }
}   