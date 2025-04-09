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
    public function details(int $id, VoyageRepository $voyageRepository): Response
    {
        $voyage = $voyageRepository->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage not found.');
        }

        return $this->render('voyages/details-voyage.html.twig', [
            'voyage' => $voyage
        ]);
    }

    #[Route('/voyages/ajout', name: 'ajout_voyage')]
    public function add(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
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

            // n5allouha mba3ed image
            /*
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $imageErrors = $validator->validate($imageFile, [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, JPEG, PNG)',
                    ])
                ]);

                if (count($imageErrors) > 0) {
                    $this->addFlash('error', 'Image invalide.');
                    return $this->redirectToRoute('ajout_voyage');
                }

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('voyage_images_directory'),
                        $newFilename
                    );
                    $voyage->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
            }
            */

            $voyage->setNote(0);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage ajouté avec succès.');
            return $this->redirectToRoute('liste_voyages');
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
    public function edit(Voyage $voyage, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
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

            // Handle image upload logic (if needed)
            /*
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $imageErrors = $validator->validate($imageFile, [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, JPEG, PNG)',
                    ])
                ]);

                if (count($imageErrors) > 0) {
                    $this->addFlash('error', 'Image invalide.');
                    return $this->redirectToRoute('modifier_voyage', ['id' => $voyage->getId()]);
                }

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('voyage_images_directory'),
                        $newFilename
                    );
                    $voyage->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
            }
            */

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
        $feedbacks = $voyage->getFeedbacks(); // assuming the relation is properly set

        return $this->json([
            'feedbacks' => $feedbacks
        ]);
    }

}
