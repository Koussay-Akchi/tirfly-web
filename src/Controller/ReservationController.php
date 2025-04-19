<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ClientRepository;
use App\Repository\FoyerRepository;
use App\Repository\HotelRepository;
use App\Repository\LogementRepository;
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
use App\Repository\HebergementRepository;
use App\Repository\PackRepository;
use App\Entity\Sejour;
use App\Entity\Voyage;
use App\Entity\Hebergement;
use App\Entity\Pack;

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

    #[Route('/reservations/new/{type}/{id}', name: 'ajout_reservation')]
    public function new(
        string $type,
        int $id,
        Request $request,
        VoyageRepository $voyageRepository,
        HebergementRepository $hebergementRepository,
        PackRepository $packRepository,
        ClientRepository $clientRepository,
        HotelRepository $hotelRepository,
        FoyerRepository $foyerRepository,
        LogementRepository $logementRepository,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();
        $client = $clientRepository->find($user->getId());
        if (!$client) {
            $this->addFlash('error', 'Vous devez être connecté pour réserver.');
            return $this->redirectToRoute('app_login');
        }

        $selectedEntity = null;
        switch ($type) {
            case 'voyage':
                $selectedEntity = $voyageRepository->find($id);
                break;
            case 'hebergement':
                $selectedEntity = $hebergementRepository->find($id);
                break;
            case 'pack':
                $selectedEntity = $packRepository->find($id);
                break;
            default:
                throw $this->createNotFoundException("Type de réservation non supporté.");
        }
        if (!$selectedEntity) {
            throw $this->createNotFoundException("L'élément sélectionné est introuvable.");
        }

        $selectedPrice = 0.0;
        if ($type === 'hebergement') {
            $priceEntity = $hotelRepository->find($id);
            if (!$priceEntity) {
                $priceEntity = $logementRepository->find($id);
            }
            if (!$priceEntity) {
                $priceEntity = $foyerRepository->find($id);
            }
            if ($priceEntity) {
                if (method_exists($priceEntity, 'getPrix')) {
                    $selectedPrice = $priceEntity->getPrix();
                } elseif (method_exists($priceEntity, 'getFrais')) {
                    $selectedPrice = $priceEntity->getFrais();
                }
            } else {
                $selectedPrice = 0.0;
            }
        } elseif ($type === 'voyage' || $type === 'pack') {
            $selectedPrice = $selectedEntity->getPrix();
        }

        $data = [
            'personCount' => 1,
            'jours' => 1,
            'reservationDate' => (new \DateTime())->format('Y-m-d'),
            'coupon' => null,
        ];

        if ($request->isMethod('POST')) {
            $personCount = (int) $request->request->get('personCount', 1);
            $jours = (int) $request->request->get('jours', 1);
            $reservationDate = new \DateTime($request->request->get('reservationDate'));
            $couponCode = $request->request->get('coupon');

            $reservation = new Reservation();
            $reservation->setClient($client);
            $reservation->setNombrePersonnes($personCount);
            $reservation->setStatut('EN_ATTENTE');

            if ($type === 'hebergement') {
                $sejour = new Sejour();
                $sejour->setHebergement($selectedEntity);
                $sejour->setJours($jours);
                $em->persist($sejour);
                $reservation->setSejour($sejour);
                $reservation->setPrixTotal($personCount * $selectedPrice * $jours);
                $reservation->setDateReservation($reservationDate);
            } elseif ($type === 'voyage') {
                $reservation->setVoyage($selectedEntity);
                $reservation->setPrixTotal($personCount * $selectedPrice);
                $reservation->setDateReservation(new \DateTime());
            } elseif ($type === 'pack') {
                $reservation->setPack($selectedEntity);
                $reservation->setPrixTotal($personCount * $selectedPrice);
                $reservation->setDateReservation(new \DateTime());
            }

            if ($couponCode) {
                //mizel
                $couponRemise = 10.0;
                $newPrice = $reservation->getPrixTotal() * ((100.0 - $couponRemise) / 100.0);
                $reservation->setPrixTotal($newPrice);
            }

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Réservation confirmée !');
            return $this->redirectToRoute('historique_reservations');
        }

        return $this->render('reservations/form-reservation.html.twig', [
            'type' => $type,
            'selected' => $selectedEntity,
            'formData' => $data,
            'selectedPrice' => $selectedPrice,
        ]);
    }


}
