<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Repository\DestinationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\DestinationType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DestinationController extends AbstractController
{
    #[Route('/destinations', name: 'liste_destinations')]     public function index(DestinationRepository $destinationRepository): Response
    {

        $destinations = $destinationRepository->findAll();
        
        return $this->render('destinations/liste-destinations.html.twig', [
            'destinations' => $destinations
        ]);
    }

    #[Route('/destination/{id}', name: 'details_destination')]
    public function details(int $id, DestinationRepository $destinationRepository): Response
    {
        $destination = $destinationRepository->find($id);

        if (!$destination) {
            throw $this->createNotFoundException('Destination not found.');
        }

        return $this->render('destinations/details-destination.html.twig', [
            'destination' => $destination
        ]);
    }


    /*
    #[Route('/destinations/ajout', name: 'ajout_destination')]
    public function add(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateConstraints = new Assert\Collection([
                'dateDepart' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)],
                'dateArrive' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)]
            ]);

            $dateErrors = $validator->validate([
                'dateDepart' => $destination->getDateDepart(),
                'dateArrive' => $destination->getDateArrive(),
            ], $dateConstraints);

            if (count($dateErrors) > 0 || $destination->getDateArrive() < $destination->getDateDepart()) {
                $this->addFlash('error', "La date d'arrivée doit être après la date de départ.");
                return $this->redirectToRoute('ajout_destination');
            }

            $priceErrors = $validator->validate($destination->getPrix(), [
                new Assert\NotNull(),
                new Assert\Type('numeric'),
                new Assert\Positive(),
            ]);

            if (count($priceErrors) > 0) {
                $this->addFlash('error', "Le prix doit être un nombre positif.");
                return $this->redirectToRoute('ajout_destination');
            }

            // n5allouha mba3ed image
            
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
                    return $this->redirectToRoute('ajout_destination');
                }

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('destination_images_directory'),
                        $newFilename
                    );
                    $destination->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
            }
            

            $destination->setNote(0);
            $entityManager->persist($destination);
            $entityManager->flush();

            $this->addFlash('success', 'Destination ajouté avec succès.');
            return $this->redirectToRoute('liste_destinations');
        }

        return $this->render('destinations/ajout-destination.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    */

    #[Route('/admin/destinations', name: 'admin_liste_destinations')]
    public function adminDestinations(DestinationRepository $destinationRepository): Response
    {
        $destinations = $destinationRepository->findAll();

        return $this->render('destinations/tableau-destinations.html.twig', [
            'destinations' => $destinations,
        ]);
    }

    /*
        #[Route('/admin/destinations/edit/{id}', name: 'edit_destination')]
        public function edit(Destination $destination, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
        {
            $form = $this->createForm(DestinationType::class, $destination);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $dateConstraints = new Assert\Collection([
                    'dateDepart' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)],
                    'dateArrive' => [new Assert\NotNull(), new Assert\Type(\DateTimeInterface::class)]
                ]);

                $dateErrors = $validator->validate([
                    'dateDepart' => $destination->getDateDepart(),
                    'dateArrive' => $destination->getDateArrive(),
                ], $dateConstraints);

                if (count($dateErrors) > 0 || $destination->getDateArrive() < $destination->getDateDepart()) {
                    $this->addFlash('error', "La date d'arrivée doit être après la date de départ.");
                    return $this->redirectToRoute('modifier_destination', ['id' => $destination->getId()]);
                }

                $priceErrors = $validator->validate($destination->getPrix(), [
                    new Assert\NotNull(),
                    new Assert\Type('numeric'),
                    new Assert\Positive(),
                ]);

                if (count($priceErrors) > 0) {
                    $this->addFlash('error', "Le prix doit être un nombre positif.");
                    return $this->redirectToRoute('modifier_destination', ['id' => $destination->getId()]);
                }

                // Handle image upload logic (if needed)
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
                        return $this->redirectToRoute('modifier_destination', ['id' => $destination->getId()]);
                    }

                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('destination_images_directory'),
                            $newFilename
                        );
                        $destination->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                    }
                }

                $entityManager->flush();

                $this->addFlash('success', 'Destination modifié avec succès.');
                return $this->redirectToRoute('admin_liste_destinations');
            }

            return $this->render('destinations/edit-destination.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        */

    #[Route('/admin/destinations/delete/{id}', name: 'delete_destination')]
    public function delete(Destination $destination, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($destination);
        $entityManager->flush();
        $this->addFlash('success', 'Destination supprimé avec succès.');
        return $this->redirectToRoute('admin_liste_destinations');
    }

}
