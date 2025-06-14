<?php
namespace App\Controller;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Foyer;
use App\Entity\Hebergement;
use App\Entity\Hotel;
use App\Entity\Logement;
use App\Form\HebergementType;
use App\Repository\HebergementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Knp\Component\Pager\PaginatorInterface; 
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use App\Service\EmailService;

class HebergementController extends AbstractController
    {#[Route('/hebergements/ajout', name: 'app_hebergement_add')]
        public function add(
            Request $request, 
            EntityManagerInterface $em, 
            EmailService $emailService, // <-- Inject your EmailService
            UserRepository $userRepository
        ): Response 
        {
            $hebergement = new Hebergement();
            $type = $request->query->get('type');
        
            $form = $this->createForm(HebergementType::class, $hebergement, [
                'type' => $type,
            ]);
            $form->handleRequest($request);
        
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $imageFile = $form->get('image')->getData();
                    $generatedImage = $request->request->get('generated_image');
        
                    if ($imageFile) {
                        $stream = fopen($imageFile->getPathname(), 'rb');
                        $hebergement->setImage(stream_get_contents($stream));
                        fclose($stream);
                    } elseif ($generatedImage) {
                        if (str_starts_with($generatedImage, 'data:image/')) {
                            [$meta, $content] = explode(',', $generatedImage, 2);
                            $imageData = base64_decode($content);
                            $hebergement->setImage($imageData);
                        }
                    }
        
                    $em->persist($hebergement);
        
                    switch ($form->get('type')->getData()) {
                        case 'hotel':
                            $hotel = new Hotel();
                            $hotel->setPrix($form->get('prix')->getData())
                                ->setHebergement($hebergement);
                            $em->persist($hotel);
                            break;
        
                        case 'logement':
                            $logement = new Logement();
                            $logement->setPrix($form->get('prix')->getData())
                                ->setJourDispo($form->get('jourDispo')->getData())
                                ->setHebergement($hebergement);
                            $em->persist($logement);
                            break;
        
                        case 'foyer':
                            $foyer = new Foyer();
                            $documentsFile = $form->get('documents')->getData();
                            $fileName = $documentsFile ? $this->uploadFile($documentsFile) : null;
        
                            $foyer->setFrais($form->get('frais')->getData())
                                ->setType($form->get('typeFoyer')->getData())
                                ->setDocuments($fileName)
                                ->setHebergement($hebergement);
                            $em->persist($foyer);
                            break;
                    }
        
                    $em->flush();
        
                    // 🔥 Send the email after saving
                    $adminEmail = 'chahd.khaldi@esprit.tn '; // You can change this or fetch from config
                    $subject = 'Nouveau hébergement ajouté';
                    $body = 'Un nouvel hébergement a été ajouté avec succès. ID: ' . $hebergement->getId();
        
                    $emailService->sendEmail($adminEmail, $subject, $body);
        
                    $this->addFlash('success', 'Hébergement ajouté et notification envoyée avec succès.');
                    return $this->redirectToRoute('admin_liste_hebergements');
        
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
                }
            } elseif ($form->isSubmitted()) {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        
            return $this->render('hebergement/add.html.twig', [
                'form' => $form->createView(),
                'initial_type' => $type,
            ]);
        }
        
        /**
         * Handle document file upload for foyer
         */
        private function uploadFile(UploadedFile $file): ?string
        {
            $uploadDir = $this->getParameter('documents_directory');
            $fileName = uniqid() . '.' . $file->guessExtension();
        
            $file->move($uploadDir, $fileName);
        
            return $fileName;
        }
        

    #[Route('/admin/hebergements/{id}/edit', name: 'edit_hebergement')]
    public function edit(Hebergement $hebergement, Request $request, EntityManagerInterface $em): Response
    {
        // Determine hebergement type and specific data
        $type = null;
        $specific = null;
    
        if (!empty($hebergement->getHotels())) {
            $type = 'hotel';
            $specific = $hebergement->getHotels()[0];
        } elseif (!empty($hebergement->getLogements())) {
            $type = 'logement';
            $specific = $hebergement->getLogements()[0];
        } elseif (!empty($hebergement->getFoyers())) {
            $type = 'foyer';
            $specific = $hebergement->getFoyers()[0];
        }
    
        if (!$type) {
            $this->addFlash('error', 'Type d\'hébergement non reconnu.');
            return $this->redirectToRoute('admin_liste_hebergements');
        }
    
        // Create the form
        $form = $this->createForm(HebergementType::class, $hebergement, [
            'type' => $type,
        ]);
        
        // Set initial data for specific fields
        if ($specific) {
            switch ($type) {
                case 'hotel':
                    $form->get('prix')->setData($specific->getPrix());
                    break;
                case 'logement':
                    $form->get('prix')->setData($specific->getPrix());
                    $form->get('jourDispo')->setData($specific->getJourDispo());
                    break;
                case 'foyer':
                    $form->get('frais')->setData($specific->getFrais());
                    $form->get('typeFoyer')->setData($specific->getType());
                    break;
            }
        }
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
    
            if ($form->isValid()) {
                try {
                    // Handle image upload as BLOB
                    $imageFile = $form->get('image')->getData();
                    if ($imageFile) {
                        $stream = fopen($imageFile->getPathname(), 'rb');
                        $hebergement->setImage(stream_get_contents($stream));
                        fclose($stream);
                    }
    
                    // Handle specific accommodation types
                    switch ($type) {
                        case 'hotel':
                            $specific->setPrix($form->get('prix')->getData());
                            break;
                        case 'logement':
                            $specific->setPrix($form->get('prix')->getData());
                            $specific->setJourDispo($form->get('jourDispo')->getData());
                            break;
                        case 'foyer':
                            $specific->setFrais($form->get('frais')->getData());
                            $specific->setType($form->get('typeFoyer')->getData());
                            
                            $documentsFile = $form->get('documents')->getData();
                            if ($documentsFile) {
                                $fileName = $this->uploadFile($documentsFile);
                                $specific->setDocuments($fileName);
                            }
                            break;
                    }
    
                    $em->flush();
                    $this->addFlash('success', 'Hébergement modifié avec succès.');
                    return $this->redirectToRoute('admin_liste_hebergements');
    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
                }
            }
        }
    
        // Prepare image data for template
        $imageData = null;
        if ($hebergement->getImage()) {
            $imageData = 'data:image/jpeg;base64,' . base64_encode($hebergement->getImage());
        }
    
        return $this->render('hebergement/edit.html.twig', [
            'form' => $form->createView(),
            'hebergement' => $hebergement,
            'initial_type' => $type,
            'image_data' => $imageData,
        ]);
    }
    #[Route('/admin/hebergements', name: 'admin_liste_hebergements')]
    public function adminHebergements(Request $request, HebergementRepository $hebergementRepository): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');
    
        $queryBuilder = $hebergementRepository->createQueryBuilder('h');
    
        if ($search) {
            $queryBuilder->andWhere('h.nom LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }
    
        if ($type) {
            if ($type === 'hotel') {
                $queryBuilder->join('h.hotels', 'ho');
            } elseif ($type === 'logement') {
                $queryBuilder->join('h.logements', 'lo');
            } elseif ($type === 'foyer') {
                $queryBuilder->join('h.foyers', 'fo');
            }
        }
    
        $hebergements = $queryBuilder->getQuery()->getResult();
    
        // Encode images to base64
        foreach ($hebergements as $hebergement) {
            if ($hebergement->getImage()) {
                $hebergement->imageBase64 = base64_encode($hebergement->getImage());
            }
        }
    
        return $this->render('hebergement/list.html.twig', [
            'hebergements' => $hebergements,
        ]);
    }
    

    
    #[Route('/admin/hebergements/delete/{id}', name: 'delete_hebergement', methods: ['POST'])]
public function delete(Request $request, int $id, EntityManagerInterface $em): Response
{
    $hebergement = $em->getRepository(Hebergement::class)->find($id);
    
    if (!$hebergement) {
        $this->addFlash('error', 'Hébergement non trouvé');
        return $this->redirectToRoute('admin_liste_hebergements');
    }

    if ($this->isCsrfTokenValid('delete'.$hebergement->getId(), $request->request->get('_token'))) {
        // First delete all related hotels if they exist
        foreach ($hebergement->getHotels() as $hotel) {
            $em->remove($hotel);
        }
        
        // Delete related logements if they exist
        foreach ($hebergement->getLogements() as $logement) {
            $em->remove($logement);
        }
        
        // Delete related foyers if they exist
        foreach ($hebergement->getFoyers() as $foyer) {
            // Optionally delete the uploaded documents file
            if ($foyer->getDocuments()) {
                $filePath = $this->getParameter('documents_directory').'/'.$foyer->getDocuments();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $em->remove($foyer);
        }
        
        $em->remove($hebergement);
        $em->flush();
        $this->addFlash('success', 'Hébergement supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Jeton CSRF invalide.');
    }
    
    return $this->redirectToRoute('admin_liste_hebergements');
}



#[Route('/hebergement/{id}', name: 'hebergement_show')]
public function show(
    Hebergement $hebergement, 
    HttpClientInterface $httpClient,
    EntityManagerInterface $entityManager
): Response
{
    $type = null;
    $specific = null;
    
    if (!empty($hebergement->getHotels())) {
        $type = 'hotel';    
        $specific = $hebergement->getHotels()[0];
    } elseif (!empty($hebergement->getLogements())) {
        $type = 'logement';
        $specific = $hebergement->getLogements()[0];
    } elseif (!empty($hebergement->getFoyers())) {
        $type = 'foyer';
        $specific = $hebergement->getFoyers()[0];
    }

    // Get the image data
    $imageData = $hebergement->getImage();
    
    // Geocode location if destination exists but has no coordinates
    $destination = $hebergement->getDestination();
    if ($destination && (!$destination->getLatitude() || !$destination->getLongitude())) {
        try {
            $response = $httpClient->request(
                'GET',
                'https://nominatim.openstreetmap.org/search',
                [
                    'query' => [
                        'q' => $destination->getVille().','.$destination->getPays(),
                        'format' => 'json',
                        'limit' => 1
                    ],
                    'headers' => [
                        'User-Agent' => 'YourAppName/1.0 (your@email.com)' // Required by Nominatim
                    ]
                ]
            );
            
            $data = $response->toArray();
            if (!empty($data[0]['lat'])) {
                $destination->setLatitude((float)$data[0]['lat'])
                           ->setLongitude((float)$data[0]['lon']);
                
                // Persist the coordinates using injected EntityManager
                $entityManager->persist($destination);
                $entityManager->flush();
            }
        } catch (\Exception $e) {
            // Silently fail - we'll just show the address without map
        }
    }

    return $this->render('hebergement/show.html.twig', [
        'hebergement' => $hebergement,
        'imageData' => $imageData ? base64_encode($imageData) : null,
        'type' => $type,
        'specific' => $specific,
        'hasCoordinates' => $destination && $destination->getLatitude() && $destination->getLongitude(),
        'destination' => $destination
    ]);
}

#[Route('/hebergement/{id}/specific-fields', name: 'hebergement_specific_fields')]
public function specificFields(Hebergement $hebergement): Response
{
    $imageData = $hebergement->getImage() ? base64_encode($hebergement->getImage()) : null;
    
    $templateData = [
        'hebergement' => $hebergement,
        'imageData' => $imageData,
        'services' => $hebergement->getServices(),
        'type' => null,
        'specific' => null,
        'priceLabel' => 'Prix',
        'price' => null
    ];

    // Check for hotel with null check
    if (!empty($hebergement->getHotels()) && $hebergement->getHotels()[0] !== null) {
        $hotel = $hebergement->getHotels()[0];
        $templateData = array_merge($templateData, [
            'type' => 'hotel',
            'specific' => $hotel,
            'priceLabel' => 'Prix par nuit',
            'price' => $hotel->getPrix() ?? null
        ]);
    } 
    // Check for logement with null check
    elseif (!empty($hebergement->getLogements()) && $hebergement->getLogements()[0] !== null) {
        $logement = $hebergement->getLogements()[0];
        $templateData = array_merge($templateData, [
            'type' => 'logement',
            'specific' => $logement,
            'priceLabel' => 'Prix par mois',
            'price' => $logement->getPrix() ?? null
        ]);
    } 
    // Check for foyer with null check
    elseif (!empty($hebergement->getFoyers()) && $hebergement->getFoyers()[0] !== null) {
        $foyer = $hebergement->getFoyers()[0];
        $templateData = array_merge($templateData, [
            'type' => 'foyer',
            'specific' => $foyer,
            'priceLabel' => 'Frais de séjour',
            'price' => $foyer->getFrais() ?? null
        ]);
    }

    return $this->render('hebergement/_specific_fields.html.twig', $templateData);
}
#[Route('/hebergements', name: 'app_hebergement_index')]
public function index(
    Request $request,
    HebergementRepository $repository,
    PaginatorInterface $paginator
): Response {
    // Traitement des paramètres
    $amenities = array_filter(
        array_map('intval', (array)($request->query->all('amenities') ?? [])),
        fn($item) => $item > 0
    );

    $searchQuery = trim((string)$request->query->get('query', ''));
    $type = in_array($request->query->get('type'), ['hotel', 'logement', 'foyer', ''], true) 
        ? $request->query->get('type') 
        : '';
    
    $destination = max(0, (int)$request->query->get('destination', 0));
    $maxPrice = min(10000, max(0, (float)$request->query->get('max_price', 500.0)));
    $minRating = min(5, max(0, (int)$request->query->get('min_rating', 0)));
    $sort = in_array($request->query->get('sort'), ['price_asc', 'price_desc', 'rating_desc', 'name_asc', ''], true)
        ? $request->query->get('sort')
        : '';
    
    $page = max(1, $request->query->getInt('page', 1));

    // Construction de la query
    $query = $repository->createSearchQueryBuilder(
        $searchQuery,
        $type,
        $destination,
        $maxPrice,
        $minRating,
        $amenities,
        $sort
    );

   // Assuming the query includes aggregates or HAVING, adjust like so:
// Pagination with output walkers enabled
$hebergements = $paginator->paginate(
    $query,
    $page,
    5,
    [
        'wrap-queries' => true,  // This enables output walkers
        'distinct' => false,
        'alias' => 'h',          // Must match your query builder alias
        'defaultSortFieldName' => 'h.id',
        'defaultSortDirection' => 'desc'
    ]
);

    // Prepare image data for binary images
    $imageData = [];
    foreach ($hebergements as $hebergement) {
        if ($hebergement->getImage() && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $hebergement->getImage()) && 
            !str_starts_with($hebergement->getImage(), '/uploads/') && 
            !str_starts_with($hebergement->getImage(), 'http')) {
            // Assume it's binary data
            $imageData[$hebergement->getId()] = base64_encode($hebergement->getImage());
        }
    }

    // Rendu de la bonne vue
    return $this->render('hebergement/cards.html.twig', [
        'hebergements' => $hebergements,
        'destinations' => $repository->findAllDestinations(),
        'allServices' => $repository->findAllServices(),
        'current_filters' => [
            'query' => $searchQuery,
            'type' => $type,
            'destination' => $destination,
            'max_price' => $maxPrice,
            'min_rating' => $minRating,
            'amenities' => $amenities,
            'sort' => $sort
        ],
        'imageData' => $imageData // Pass the prepared image data to the template
    ]);
}

#[Route('/api/hebergement/autocomplete', name: 'api_hebergement_autocomplete')]
public function autocomplete(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $query = $request->query->get('query', '');
    
    $results = $entityManager
        ->getRepository(Hebergement::class)
        ->createQueryBuilder('h')
        ->where('h.nom LIKE :query')
        ->setParameter('query', '%'.$query.'%')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
    
    $formattedResults = array_map(function($hebergement) {
        return $hebergement->getNom();
    }, $results);
    
    return new JsonResponse($formattedResults);
}

}