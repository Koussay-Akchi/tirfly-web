<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/chat', name: 'chat_index', defaults: ['chat' => null])]
    #[Route('/chat/{chat<\d+>}', name: 'chat_show')] // Restrict {chat} to digits
    public function index(
        ?Chat $chat,
        ChatRepository $chatRepository,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
    
        $chats = $chatRepository->findChatsForUser($user);
    
        if (empty($chats) && in_array('ROLE_CLIENT', $user->getRoles())) {
            // Find support users
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult(User::class, 'u');
            $rsm->addFieldResult('u', 'id', 'id');
            $rsm->addFieldResult('u', 'dateCreation', 'dateCreation');
            $rsm->addFieldResult('u', 'email', 'email');
            $rsm->addFieldResult('u', 'MotDePasse', 'motDePasse');
            $rsm->addFieldResult('u', 'nom', 'nom');
            $rsm->addFieldResult('u', 'prenom', 'prenom');
            $rsm->addFieldResult('u', 'resetToken', 'resetToken');
            $rsm->addFieldResult('u', 'resetTokenExpiresAt', 'resetTokenExpiresAt');
    
            $query = $em->createNativeQuery(
                'SELECT id, dateCreation, email, MotDePasse, nom, prenom, resetToken, resetTokenExpiresAt 
                 FROM users 
                 WHERE UPPER(role) = :role',
                $rsm
            );
            $query->setParameter('role', 'SUPPORT');
            $supportUsers = $query->getResult();
    
            if (empty($supportUsers)) {
                $allRoles = $em->createNativeQuery('SELECT DISTINCT role FROM users', new ResultSetMapping())->getResult();
                throw $this->createNotFoundException(
                    'Aucun utilisateur de support disponible. Rôles trouvés : ' . json_encode($allRoles)
                );
            }
    
            // Find the support user with the least chats
            $supportChatCounts = [];
            foreach ($supportUsers as $support) {
                $chatCount = $chatRepository->createQueryBuilder('c')
                    ->select('COUNT(c.id)')
                    ->where('c.support = :support')
                    ->setParameter('support', $support)
                    ->getQuery()
                    ->getSingleScalarResult();
                $supportChatCounts[$support->getId()] = $chatCount;
            }
    
            // Find the minimum chat count
            $minChatCount = min($supportChatCounts);
    
            // Get all support users with the minimum chat count
            $leastBusySupports = array_filter($supportUsers, function ($support) use ($supportChatCounts, $minChatCount) {
                return $supportChatCounts[$support->getId()] === $minChatCount;
            });
    
            // Pick a random support user from the least busy ones
            $randomSupport = $leastBusySupports[array_rand($leastBusySupports)];
    
            // Create new chat
            $newChat = new Chat();
            $newChat->setClient($user);
            $newChat->setSupport($randomSupport);
            $em->persist($newChat);
            $em->flush();
    
            $chats = [$newChat];
            $chat = $newChat;
        }
    
        $messages = [];
        if ($chat) {
            if ($chat->getClient() !== $user && $chat->getSupport() !== $user) {
                throw $this->createAccessDeniedException('Vous n’avez pas accès à ce chat.');
            }
    
            $messages = $messageRepository->createQueryBuilder('m')
                ->where('m.chat = :chat')
                ->setParameter('chat', $chat)
                ->orderBy('m.dateEnvoi', 'ASC')
                ->getQuery()
                ->getResult();
        }
    
        return $this->render('chat/index.html.twig', [
            'chats' => $chats,
            'selectedChat' => $chat,
            'messages' => $messages,
        ]);
    }

    #[Route('/chat/init/{supportId}', name: 'chat_init')]
    public function initChat(
        int $supportId,
        ChatRepository $chatRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $existingChat = $chatRepository->createQueryBuilder('c')
            ->where('c.client = :client')
            ->andWhere('c.support = :support')
            ->setParameter('client', $user)
            ->setParameter('support', $supportId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$existingChat) {
            $supportUser = $em->getRepository(User::class)->find($supportId);
            if (!$supportUser) {
                throw $this->createNotFoundException('Utilisateur support non trouvé.');
            }

            $chat = new Chat();
            $chat->setClient($user);
            $chat->setSupport($supportUser);
            $em->persist($chat);
            $em->flush();
        }

        return $this->redirectToRoute('chat_index');
    }

    #[Route('/chat/message', name: 'chat_message', methods: ['POST'])]
    public function saveMessage(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->logger->debug('saveMessage called', [
            'content' => $request->getContent(),
            'headers' => $request->headers->all()
        ]);

        $user = $this->getUser();
        if (!$user) {
            $this->logger->error('User not authenticated');
            return new JsonResponse(['error' => 'Utilisateur non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            $this->logger->error('Invalid JSON', ['content' => $request->getContent()]);
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        if (!isset($data['chatId'], $data['message'], $data['userId'])) {
            $this->logger->error('Missing required fields', ['data' => $data]);
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $chat = $em->getRepository(Chat::class)->find($data['chatId']);
        if (!$chat) {
            $this->logger->error('Chat not found', ['chatId' => $data['chatId']]);
            return new JsonResponse(['error' => 'Chat non trouvé'], 404);
        }

        if ($chat->getClient() !== $user && $chat->getSupport() !== $user) {
            $this->logger->error('Unauthorized access', [
                'chatId' => $data['chatId'],
                'userId' => $user->getId()
            ]);
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        $message = new Message();
        $message->setChat($chat);
        $message->setExpediteur($user);
        $message->setMessage($data['message']);
        $message->setDateEnvoi(new \DateTime());

        try {
            $em->persist($message);
            $em->flush();
            $this->logger->info('Message saved successfully', [
                'messageId' => $message->getId(),
                'chatId' => $data['chatId']
            ]);
            return new JsonResponse(['status' => 'Message saved'], 200);
        } catch (\Exception $e) {
            $this->logger->error('Database error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }
}