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
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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


    #[Route('/admin/destinations/ajout', name: 'ajout_destination')]
    public function ajoutDestination(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('destinations_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $destination->setImage($newFilename);
            }

            $climatValue = $request->request->get('climatSelect');

            if ($climatValue !== null) {
                $destination->setClimat((int) $climatValue);
            }

            $entityManager->persist($destination);
            $entityManager->flush();

            $this->addFlash('success', 'Destination ajoutée avec succès!');
            return $this->redirectToRoute('admin_liste_destinations');
        }

        return $this->render('destinations/ajout-destination.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/destinations', name: 'admin_liste_destinations')]
    public function adminDestinations(Request $request, DestinationRepository $destinationRepository, PaginatorInterface $paginator): Response
    {
        $query = $destinationRepository->createQueryBuilder('v')->getQuery();
        $destinations = $destinationRepository->findAll();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('destinations/tableau-destinations.html.twig', [
            'destinations' => $pagination,
            'destinationsAll' => $destinations,
        ]);
    }

    #[Route('/admin/destinations/edit/{id}', name: 'edit_destination')]
    public function edit(
        Destination $destination,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('destinations_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }

                $destination->setImage($newFilename);
            }

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
