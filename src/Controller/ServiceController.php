<?php
// src/Controller/ServiceController.php
namespace App\Controller;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\HebergementRepository;

#[Route('/admin/services')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(
        ServiceRepository $serviceRepository,
        HebergementRepository $hebergementRepository,
        Request $request
    ): Response {
        // Get filter parameters from request and cast to correct types
        $searchTerm = $request->query->get('search');
        $hebergementId = $request->query->has('hebergement') ? 
            (int)$request->query->get('hebergement') : null;
        $priceSort = $request->query->get('price_sort');

        // Get all hébergements for the filter dropdown
        $hebergements = $hebergementRepository->findAll();

        // Get filtered services
        $services = $serviceRepository->findWithFilters(
            $searchTerm,
            $hebergementId,
            $priceSort
        );

        return $this->render('service/index.html.twig', [
            'services' => $services,
            'hebergements' => $hebergements,
        ]);
    }

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'Service créé avec succès!');    
            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    public function show(Service $service): Response
    {
        return $this->render('service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Service modifié avec succès!');
            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            $em->remove($service);
            $em->flush();
            $this->addFlash('success', 'Service supprimé avec succès!');
        }

        return $this->redirectToRoute('app_service_index');
    }

    // API endpoint for AJAX deletion (optional)
    #[Route('/{id}/delete', name: 'app_service_api_delete', methods: ['DELETE'])]
    public function apiDelete(Service $service, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($service);
        $em->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'Service deleted']);
    }
}