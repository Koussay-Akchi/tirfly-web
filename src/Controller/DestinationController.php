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
use App\Repository\VoyageRepository;

class DestinationController extends AbstractController
{
    #[Route('/destinations', name: 'liste_destinations')] public function index(DestinationRepository $destinationRepository): Response
    {

        $destinations = $destinationRepository->findAll();
        $climatNames = [
            1 => 'Tropical',
            2 => 'Chaud',
            3 => 'Froid',
            4 => 'Désert',
        ];

        return $this->render('destinations/liste-destinations.html.twig', [
            'destinations' => $destinations,
            'climat_names' => $climatNames,
        ]);
    }


    #[Route('/destinations/{id}', name: 'details_destination')]
    public function detailsDestinations(
        int $id,
        DestinationRepository $destinationRepository,
        VoyageRepository $voyageRepo
    ): Response {

        $destination = $destinationRepository->find($id);

        if (!$destination) {
            throw $this->createNotFoundException('Destination not found.');
        }

        $voyages = $voyageRepo->findBy(['destination' => $destination]);

        $climatNames = [
            1 => 'Tropical',
            2 => 'Chaud',
            3 => 'Froid',
            4 => 'Désert',
        ];

        return $this->render('destinations/details-destination.html.twig', [
            'destination' => $destination,
            'voyages' => $voyages,
            'climat_names' => $climatNames,
        ]);
    }


    #[Route('/destinations/ajout', name: 'ajout_destination')]
    public function ajoutDestination(Request $request, EntityManagerInterface $entityManager): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

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
            
*/
            $climatValue = $request->request->get('climatSelect');

            if ($climatValue !== null) {
                $destination->setClimat((int) $climatValue);
            }

            $entityManager->persist($destination);
            $entityManager->flush();

            $this->addFlash('success', 'Destination ajoutée avec succès!');
            return $this->redirectToRoute('liste_destinations');
        }

        return $this->render('destinations/ajout-destination.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/destinations', name: 'admin_liste_destinations')]
    public function adminDestinations(DestinationRepository $destinationRepository): Response
    {
        $destinations = $destinationRepository->findAll();

        return $this->render('destinations/tableau-destinations.html.twig', [
            'destinations' => $destinations,
        ]);
    }

    #[Route('/admin/destinations/edit/{id}', name: 'edit_destination')]
    public function edit(Destination $destination, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $climat = $request->request->get('climatSelect');
            if ($climat !== null) {
                $destination->setClimat((int) $climat);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Destination modifiée avec succès.');
            return $this->redirectToRoute('admin_liste_destinations');
        }

        return $this->render('destinations/edit-destination.html.twig', [
            'form' => $form->createView(),
            'destination' => $destination
        ]);
    }

    #[Route('/admin/destinations/delete/{id}', name: 'delete_destination')]
    public function delete(Destination $destination, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($destination);
        $entityManager->flush();
        $this->addFlash('success', 'Destination supprimé avec succès.');
        return $this->redirectToRoute('admin_liste_destinations');
    }

}
