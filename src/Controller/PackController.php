<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\PackRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;


class PackController extends AbstractController
{
    #[Route('/pack', name: 'app_pack_index')]
public function index(Request $request, PackRepository $packRepository): Response
{
    $page = $request->query->getInt('page', 1);
    $packs = $packRepository->findPaginated($page, 6);

    return $this->render('pack/index.html.twig', [
        'packs' => $packs, // Changez cette ligne pour passer l'objet paginé
    ]);
}
#[Route('/admin/pack/new', name: 'app_pack_new')]
public function new(
    Request $request, 
    EntityManagerInterface $em, 
    SluggerInterface $slugger,
    VoyageRepository $voyageRepository // Ajoutez ce paramètre
): Response {
    $pack = new Pack();
    $form = $this->createForm(PackType::class, $pack);
    
    // Récupérer tous les voyages avec leurs prix
    $voyages = $voyageRepository->findAllWithPrice();
    
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('packs_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
            }

            $pack->setImage($newFilename);
        }

        $em->persist($pack);
        $em->flush();

        $this->addFlash('success', 'Pack ajouté avec succès.');
        return $this->redirectToRoute('admin_pack_list');
    }

    return $this->render('pack/new.html.twig', [
        'form' => $form->createView(),
        'voyages' => $voyages // Passez les voyages au template
    ]);
}
#[Route('/admin/pack/edit/{id}', name: 'app_pack_edit')]
public function edit(
    Pack $pack, 
    Request $request, 
    EntityManagerInterface $em,
    SluggerInterface $slugger,
    VoyageRepository $voyageRepository
): Response {
    $form = $this->createForm(PackType::class, $pack);
    $voyages = $voyageRepository->findAllWithPrice();
    
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();
        
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('packs_images_directory'),
                    $newFilename
                );
                
                // Suppression ancienne image
                $oldImage = $pack->getImage();
                if ($oldImage && file_exists($this->getParameter('packs_images_directory').'/'.$oldImage)) {
                    unlink($this->getParameter('packs_images_directory').'/'.$oldImage);
                }
                
                $pack->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
            }
        }

        $em->flush();
        $this->addFlash('success', 'Pack modifié avec succès.');
        return $this->redirectToRoute('admin_pack_list');
    }

    return $this->render('pack/edit.html.twig', [
        'form' => $form->createView(),
        'pack' => $pack,
        'voyages' => $voyages
    ]);
}

#[Route('/admin/pack/delete/{id}', name: 'app_pack_delete')]
    public function delete(Pack $pack, EntityManagerInterface $em): Response
    {
        $em->remove($pack);
        $em->flush();

        $this->addFlash('success', 'Pack supprimé avec succès.');
        return $this->redirectToRoute('admin_pack_list');
    }

    // ----------------- PARTIE ADMIN ------------------

    #[Route('/admin/packs', name: 'admin_pack_list')]
    public function adminList(PackRepository $packRepository): Response
    {
        return $this->render('pack/tableau-pack.html.twig', [
            'packs' => $packRepository->findAll(),
        ]);
    }

   

    #[Route('/pack/{id}', name: 'app_pack_show')]
public function show(Pack $pack): Response
{
    if (!$pack) {
        throw $this->createNotFoundException('Le pack n\'existe pas.');
    }

    return $this->render('pack/show.html.twig', [
        'pack' => $pack,
    ]);
}

   
}
