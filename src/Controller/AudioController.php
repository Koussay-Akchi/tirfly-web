<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Oneup\UploaderBundle\Uploader\Storage\StorageInterface;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;
use Oneup\UploaderBundle\Controller\UploadControllerInterface;
use Oneup\UploaderBundle\Uploader\File\FileInterface;

class AudioController extends AbstractController
{
    private $audioDirectory;
    
    public function __construct(string $audioDirectory)
    {
        $this->audioDirectory = $audioDirectory;
    }
     #[Route('/audio/upload', name: 'audio_upload', methods: ['POST'])]

    public function upload(Request $request): JsonResponse
    {
        if (!$request->files->has('audio')) {
            return new JsonResponse(['error' => 'No audio file provided'], 400);
        }
        
        $audioFile = $request->files->get('audio');
        $field = $request->request->get('field', 'nom');
        
        $filename = uniqid() . '-' . $field . '.' . $audioFile->getClientOriginalExtension();
        
        try {
            $audioFile->move($this->audioDirectory, $filename);
            
            return new JsonResponse([
                'success' => true,
                'filename' => $filename,
                'field' => $field,
                'path' => $this->audioDirectory . '/' . $filename
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'erreur enregistrement: ' . $e->getMessage()], 500);
        }
    }
}