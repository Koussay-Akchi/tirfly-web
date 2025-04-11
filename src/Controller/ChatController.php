<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private EntityManagerInterface $em;
    private ChatRepository $chatRepository;
    private MessageRepository $messageRepository;
    private UserRepository $userRepository;

    // Emails des utilisateurs prédéfinis
    private const FIXED_CLIENT_EMAIL = 'manar@email.com';
    private const FIXED_SUPPORT_EMAIL = 'support@email.com';

    public function __construct(
        EntityManagerInterface $em,
        ChatRepository $chatRepository,
        MessageRepository $messageRepository,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->chatRepository = $chatRepository;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/chat', name: 'chat_index')]
    public function index(): Response
    {
        // Récupérer les utilisateurs prédéfinis
        $client = $this->getFixedClient();
        $support = $this->getFixedSupport();

        // S'assurer qu'un chat existe entre eux
        $chat = $this->ensureChatExists($client, $support);

        return $this->render('chat/index.html.twig', [
            'chats' => [$chat], // On passe seulement le chat fixe
            'currentUser' => $client, // On considère que l'utilisateur actuel est le client
        ]);
    }

    #[Route('/chat/{id}', name: 'chat_show')]
    public function show(Chat $chat): Response
    {
        $client = $this->getFixedClient();
        
        if (!$this->isUserPartOfChat($chat, $client)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette conversation.');
        }

        $messages = $this->messageRepository->findBy(['chat' => $chat], ['dateMessage' => 'ASC']);

        return $this->render('chat/show.html.twig', [
            'chat' => $chat,
            'messages' => $messages,
            'currentUser' => $client,
        ]);
    }

    #[Route('/chat/{id}/send', name: 'chat_send', methods: ['POST'])]
    public function send(Request $request, Chat $chat): Response
    {
        $client = $this->getFixedClient();

        if (!$this->isUserPartOfChat($chat, $client)) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('message'));
        if (!empty($content)) {
            $message = new Message();
            $message->setMessage($content);
            $message->setExpediteur($client);
            $message->setDateMessage(new DateTime());
            $message->setChat($chat);

            $this->em->persist($message);
            $this->em->flush();
        }

        return $this->redirectToRoute('chat_show', ['id' => $chat->getId()]);
    }

    /**
     * Récupère l'utilisateur client prédéfini
     */
    private function getFixedClient(): User
    {
        $client = $this->userRepository->findOneBy(['email' => self::FIXED_CLIENT_EMAIL]);
        
        if (!$client) {
            throw $this->createNotFoundException('Client fixe non trouvé!');
        }

        return $client;
    }

    /**
     * Récupère l'utilisateur support prédéfini
     */
    private function getFixedSupport(): User
    {
        $support = $this->userRepository->findOneBy(['email' => self::FIXED_SUPPORT_EMAIL]);
        
        if (!$support) {
            throw $this->createNotFoundException('Support fixe non trouvé!');
        }

        return $support;
    }

    /**
     * Vérifie si l'utilisateur fait partie du chat
     */
    private function isUserPartOfChat(Chat $chat, User $user): bool
    {
        return $chat->getClient()->getId() === $user->getId() 
            || $chat->getSupport()->getId() === $user->getId();
    }

    /**
     * S'assure qu'un chat existe entre le client et le support
     */
    private function ensureChatExists(User $client, User $support): Chat
    {
        $chat = $this->chatRepository->findOneBy([
            'client' => $client,
            'support' => $support
        ]);

        if (!$chat) {
            $chat = new Chat();
            $chat->setClient($client);
            $chat->setSupport($support);
            
            $this->em->persist($chat);
            $this->em->flush();
        }

        return $chat;
    }
}