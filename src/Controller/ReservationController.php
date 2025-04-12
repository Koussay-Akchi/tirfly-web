<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ReservationType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\VoyageRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReservationController extends AbstractController
{

    #[Route('/reservations', name: 'historique_reservations')]
    public function historiqueReservations(
        Request $request,
        ReservationRepository $reservationRepository,
        PaginatorInterface $paginator,
        Security $security
    ): Response {
        $user = $security->getUser();

        if (!$user) {
            throw new AccessDeniedException("Vous devez être connecté pour accéder à cette page.");
        }

        $queryBuilder = $reservationRepository->createQueryBuilder('r')
            ->where('r.client = :user')
            ->setParameter('user', $user);

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $reservations = $query->getResult();

        return $this->render('reservations/historique-reservations.html.twig', [
            'reservations' => $pagination,
            'reservationsAll' => $reservations,
        ]);
    }


    #[Route('/admin/reservations', name: 'admin_liste_reservations')]
    public function adminReservations(Request $request, ReservationRepository $reservationRepository, PaginatorInterface $paginator): Response
    {
        $query = $reservationRepository->createQueryBuilder('v')->getQuery();
        $reservations = $reservationRepository->findAll();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('reservations/tableau-reservations.html.twig', [
            'reservations' => $pagination,
            'reservationsAll' => $reservations,
        ]);
    }
}
