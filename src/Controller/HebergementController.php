<?php
namespace App\Controller;

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

class HebergementController extends AbstractController
    { #[Route('/hebergements/ajout', name: 'app_hebergement_add')]
        public function add(Request $request, EntityManagerInterface $em): Response
        {
            $hebergement = new Hebergement();
            $type = $request->query->get('type');
        
            $form = $this->createForm(HebergementType::class, $hebergement, [
                'type' => $type,
            ]);
        
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
        
                        $type = $form->get('type')->getData();
                        $em->persist($hebergement);
        
                        // Handle specific accommodation types
                        switch ($type) {
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
                        $this->addFlash('success', 'Hébergement ajouté avec succès.');
                        return $this->redirectToRoute('admin_liste_hebergements');
        
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
                    }
                }
            }
        
            return $this->render('hebergement/add.html.twig', [
                'form' => $form->createView(),
                'initial_type' => $type,
            ]);
        }
        
    
    private function uploadFile(UploadedFile $file): string
    {
        $fileName = uniqid().'.'.$file->guessExtension();
        $file->move($this->getParameter('documents_directory'), $fileName);
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
    public function adminHebergements(HebergementRepository $hebergementRepository): Response
    {
        $hebergements = $hebergementRepository->findAllWithDetails();
        
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
    public function show(Hebergement $hebergement): Response
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
    
        // Get the image data (will be handled by the getImage() method)
        $imageData = $hebergement->getImage();
        
        return $this->render('hebergement/show.html.twig', [
            'hebergement' => $hebergement,
            'imageData' => $imageData ? base64_encode($imageData) : null,
            'type' => $type,
            'specific' => $specific,
        ]);
    }

    #[Route('/hebergement/{id}/specific-fields', name: 'hebergement_specific_fields')]
public function specificFields(Hebergement $hebergement): Response
{
    $type = null;
    $specific = null;
    $services = $hebergement->getServices(); // Get services associated with this hebergement

    // Check type of Hebergement and get the specific entity
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

    return $this->render('hebergement/_specific_fields.html.twig', [
        'hebergement' => $hebergement,
        'type' => $type,
        'specific' => $specific,
        'services' => $services, // Pass services to the template
    ]);
}
#[Route('/hebergements', name: 'app_hebergement_index')]
public function index(HebergementRepository $hebergementRepository): Response
{
    $hebergements = $hebergementRepository->findAll(); // <-- tu dois récupérer les hébergements ici

    $imageData = [];

    foreach ($hebergements as $hebergement) {
        if ($hebergement->getImage() && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $hebergement->getImage())) {
            $imageData[$hebergement->getId()] = base64_encode($hebergement->getImage());
        }
    }

    return $this->render('hebergement/cards.html.twig', [
        'hebergements' => $hebergements,
        'imageData' => $imageData,
    ]);
}


}
