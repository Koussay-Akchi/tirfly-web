<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\VoyageType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class VoyageController extends AbstractController
{
    #[Route('/voyages', name: 'liste_voyages')]
    public function index(VoyageRepository $voyageRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $dateDepart = $request->query->get('dateDepart');
        $dateArrive = $request->query->get('dateArrive');
        $country = $request->query->get('country');

        $voyages = $voyageRepository->filterVoyages($search, $dateDepart, $dateArrive, $country);
        $countries = array_unique(array_map(fn($voyage) => $voyage->getDestination()->getPays(), $voyages));

        return $this->render('voyages/liste-voyages.html.twig', [
            'voyages' => $voyages,
            'countries' => $countries
        ]);
    }

    #[Route('/voyage/{id}', name: 'details_voyage')]
    public function details(int $id, VoyageRepository $voyageRepository, ReservationRepository $reservationRepository, Security $security): Response
    {
        $voyage = $voyageRepository->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage not found.');
        }

        $client = $security->getUser();
        $dejaReserve = false;

        if ($client) {
            $dejaReserve = $reservationRepository->findOneBy([
                'client' => $client,
                'voyage' => $voyage,
                'statut' => 'EN_ATTENTE',
            ]) !== null;
        }

        return $this->render('voyages/details-voyage.html.twig', [
            'voyage' => $voyage,
            'dejaReserve' => $dejaReserve
        ]);
    }    

    #[Route('/admin/voyage/{id}', name: 'details_voyage_admin')]
    public function detailsAdmin(int $id, VoyageRepository $voyageRepository, ReservationRepository $reservationRepository, Security $security): Response
    {
        $voyage = $voyageRepository->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage not found.');
        }

        $client = $security->getUser();
        $dejaReserve = false;

        if ($client) {
            $dejaReserve = $reservationRepository->findOneBy([
                'client' => $client,
                'voyage' => $voyage,
                'statut' => 'EN_ATTENTE',
            ]) !== null;
        }

        return $this->render('voyages/details-voyage-admin.html.twig', [
            'voyage' => $voyage,
            'dejaReserve' => $dejaReserve
        ]);
    }

    #[Route('/voyages/ajout', name: 'ajout_voyage')]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SluggerInterface $slugger,
    ): Response {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateConstraints = new Assert\Collection([
                'dateDepart' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)],
                'dateArrive' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)]
            ]);

            $dateErrors = $validator->validate([
                'dateDepart' => $voyage->getDateDepart(),
                'dateArrive' => $voyage->getDateArrive(),
            ], $dateConstraints);

            if (count($dateErrors) > 0 || $voyage->getDateArrive() < $voyage->getDateDepart()) {
                $this->addFlash('error', "La date d'arrivée doit être après la date de départ.");
                return $this->redirectToRoute('ajout_voyage');
            }

            $priceErrors = $validator->validate($voyage->getPrix(), [
                new Assert\NotNull(),
                new Assert\Type('numeric'),
                new Assert\Positive(),
            ]);

            if (count($priceErrors) > 0) {
                $this->addFlash('error', "Le prix doit être un nombre positif.");
                return $this->redirectToRoute('ajout_voyage');
            }


            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('voyages_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $voyage->setImage($newFilename);
            }

            $voyage->setNote(0);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage ajouté avec succès.');
            return $this->redirectToRoute('admin_liste_voyages');
        }

        return $this->render('voyages/ajout-voyage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/voyages', name: 'admin_liste_voyages')]
    public function adminVoyages(Request $request, VoyageRepository $voyageRepository, PaginatorInterface $paginator): Response
    {
        $query = $voyageRepository->createQueryBuilder('v')->getQuery();
        $voyages = $voyageRepository->findAll();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('voyages/admin-voyages.html.twig', [
            'voyages' => $pagination,
            'voyagesAll' => $voyages,
        ]);
    }


    #[Route('/admin/voyages/edit/{id}', name: 'edit_voyage')]
    public function edit(
        Voyage $voyage,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateConstraints = new Assert\Collection([
                'dateDepart' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)],
                'dateArrive' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)]
            ]);

            $dateErrors = $validator->validate([
                'dateDepart' => $voyage->getDateDepart(),
                'dateArrive' => $voyage->getDateArrive(),
            ], $dateConstraints);

            if (count($dateErrors) > 0 || $voyage->getDateArrive() < $voyage->getDateDepart()) {
                $this->addFlash('error', "La date d'arrivée doit être après la date de départ.");
                return $this->redirectToRoute('modifier_voyage', ['id' => $voyage->getId()]);
            }

            $priceErrors = $validator->validate($voyage->getPrix(), [
                new Assert\NotNull(),
                new Assert\Type('numeric'),
                new Assert\Positive(),
            ]);

            if (count($priceErrors) > 0) {
                $this->addFlash('error', "Le prix doit être un nombre positif.");
                return $this->redirectToRoute('modifier_voyage', ['id' => $voyage->getId()]);
            }

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('voyages_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $voyage->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Voyage modifié avec succès.');
            return $this->redirectToRoute('admin_liste_voyages');
        }

        return $this->render('voyages/edit-voyage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/voyages/delete/{id}', name: 'delete_voyage')]
    public function delete(Voyage $voyage, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($voyage);
        $entityManager->flush();
        $this->addFlash('success', 'Voyage supprimé avec succès.');
        return $this->redirectToRoute('admin_liste_voyages');
    }

    #[Route('/admin/voyages/feedback/{id}', name: 'admin_voyage_feedbacks')]
    public function listFeedbacks(Voyage $voyage): Response
    {
        $feedbacks = $voyage->getFeedbacks();

        return $this->json([
            'feedbacks' => $feedbacks
        ]);
    }

    #[Route('/voyages/assistant', name: 'assistant_voyage')]
    public function assistant(
        Request $request,
        VoyageRepository $voyageRepository,
        ReservationRepository $reservationRepository
    ): Response {

        $maxDuree = $request->query->getInt('maxDuree', 0);
        $selectedPays = $request->query->get('selectedPays');
        $maxBudget = $request->query->get('maxBudget');
        $formules = $request->query->all('formules');
        $minNote = $request->query->get('minNote', 0);
        $aiDescription = $request->query->get('aiDescription');

        $client = $this->getUser();

        $suggestions = [
            'suggestionDuree' => null,
            'suggestionPays' => null,
            'suggestionBudget' => null,
            'suggestionFormule' => null,
            'suggestionNote' => null,
        ];

        if ($client) {
            $reservations = $reservationRepository->findBy(['client' => $client]);
            $voyagesClient = [];
            foreach ($reservations as $reservation) {
                $voyage = $reservation->getVoyage();
                if ($voyage) {
                    $voyagesClient[] = $voyage;
                }
            }

            if (count($voyagesClient) > 0) {
                $totalDuree = 0;
                $totalBudget = 0;
                $totalNote = 0;
                $paysCount = [];
                $formuleCount = [];

                foreach ($voyagesClient as $voyage) {
                    if ($voyage->getDateDepart() && $voyage->getDateArrive()) {
                        $interval = $voyage->getDateDepart()->diff($voyage->getDateArrive());
                        $jours = (int) $interval->format('%a');
                        $totalDuree += $jours;
                    }
                    $totalBudget += $voyage->getPrix();
                    $totalNote += $voyage->getNote();

                    if ($voyage->getDestination()) {
                        $pays = $voyage->getDestination()->getPays();
                        if ($pays) {
                            $paysCount[$pays] = ($paysCount[$pays] ?? 0) + 1;
                        }
                    }
                    $formule = $voyage->getFormule();
                    if ($formule) {
                        $formuleCount[$formule] = ($formuleCount[$formule] ?? 0) + 1;
                    }
                }
                $suggestions['suggestionDuree'] = $totalDuree / count($voyagesClient);
                $suggestions['suggestionBudget'] = $totalBudget / count($voyagesClient);
                $suggestions['suggestionNote'] = $totalNote / count($voyagesClient);

                arsort($paysCount);
                $suggestions['suggestionPays'] = key($paysCount);

                if (!empty($formuleCount)) {
                    arsort($formuleCount);
                    $suggestions['suggestionFormule'] = key($formuleCount);
                }
            }
        }

        $qb = $voyageRepository->createQueryBuilder('v')
            ->join('v.destination', 'd')
            ->addSelect('d');

        $allVoyages = [];
        if ($maxDuree > 0) {
            $tempQb = clone $qb;

            if ($selectedPays) {
                $tempQb->andWhere('LOWER(d.pays) = LOWER(:selectedPays)')
                    ->setParameter('selectedPays', $selectedPays);
            }
            if ($maxBudget) {
                $tempQb->andWhere('v.prix <= :maxBudget')
                    ->setParameter('maxBudget', $maxBudget);
            }
            if (!empty($formules) && is_array($formules)) {
                $tempQb->andWhere('v.formule IN (:formules)')
                    ->setParameter('formules', $formules);
            }
            if ($minNote) {
                $tempQb->andWhere('v.note >= :minNote')
                    ->setParameter('minNote', $minNote);
            }

            $tempVoyages = $tempQb->getQuery()->getResult();

            foreach ($tempVoyages as $voyage) {
                if ($voyage->getDateDepart() && $voyage->getDateArrive()) {
                    $interval = $voyage->getDateDepart()->diff($voyage->getDateArrive());
                    $duree = (int) $interval->format('%a');

                    if ($duree <= $maxDuree) {
                        $allVoyages[] = $voyage;
                    }
                }
            }

            $voyages = $allVoyages;
            usort($voyages, function ($a, $b) {
                return $a->getDateDepart() <=> $b->getDateDepart();
            });
        } else {
            if ($selectedPays) {
                $qb->andWhere('LOWER(d.pays) = LOWER(:selectedPays)')
                    ->setParameter('selectedPays', $selectedPays);
            }
            if ($maxBudget) {
                $qb->andWhere('v.prix <= :maxBudget')
                    ->setParameter('maxBudget', $maxBudget);
            }
            if (!empty($formules) && is_array($formules)) {
                $qb->andWhere('v.formule IN (:formules)')
                    ->setParameter('formules', $formules);
            }
            if ($minNote) {
                $qb->andWhere('v.note >= :minNote')
                    ->setParameter('minNote', $minNote);
            }

            $qb->orderBy('v.dateDepart', 'ASC');
            $voyages = $qb->getQuery()->getResult();
        }

        $allVoyagesList = $voyageRepository->findAll();
        $allCountries = [];
        foreach ($allVoyagesList as $voyage) {
            if ($voyage->getDestination() && $voyage->getDestination()->getPays()) {
                $allCountries[] = $voyage->getDestination()->getPays();
            }
        }
        $allCountries = array_unique($allCountries);
        sort($allCountries);

        $allFormules = ['REPAS_SEUL', 'BOISSONS_SEULES', 'AUCUN', 'LES_DEUX'];

        return $this->render('voyages/assistant-voyage.html.twig', [
            'voyages' => $voyages,
            'allCountries' => $allCountries,
            'allFormules' => $allFormules,
            'filters' => [
                'maxDuree' => $maxDuree,
                'selectedPays' => $selectedPays,
                'maxBudget' => $maxBudget,
                'formules' => $formules,
                'minNote' => $minNote,
                'aiDescription' => $aiDescription,
            ],
            'suggestions' => $suggestions,
        ]);
    }

}
