<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat_index')]
    public function chat(
        Request $request,
        EntityManagerInterface $em,
        ChatRepository $chatRepository,
        MessageRepository $messageRepository
    ) {
        // Get the current logged-in user
        $user = $this->getUser();

        // Ensure the user is authenticated
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access the chat.');
        }

        // Fetch all chats where the user is either the client or support
        $chats = $chatRepository->findChatsForUser($user);

        // Get the selected chat ID from the query parameter (e.g., /chat?chat=1)
        $chatId = $request->query->get('chat');
        $selectedChat = null;
        $messages = [];
        $formView = null;

        // If a chat is selected, fetch its details and messages
        if ($chatId) {
            $selectedChat = $chatRepository->find($chatId);

            // Verify that the user is part of the selected chat
            if ($selectedChat && ($selectedChat->getClient() === $user || $selectedChat->getSupport() === $user)) {
                // Fetch all messages for the selected chat, ordered by date
                $messages = $messageRepository->findBy(['chat' => $selectedChat], ['dateEnvoi' => 'ASC']);

                // Create a form for sending a new message
                $message = new Message();
                $form = $this->createForm(MessageType::class, $message);
                $form->handleRequest($request);

                // Handle form submission
                if ($form->isSubmitted() && $form->isValid()) {
                    $message->setChat($selectedChat);
                    $message->setExpediteur($user);
                    $message->setDateEnvoi(new \DateTime());
                    $em->persist($message);
                    $em->flush();

                    // Redirect to the same chat after sending the message
                    return $this->redirectToRoute('chat_index', ['chat' => $selectedChat->getId()]);
                }

                $formView = $form->createView();
            } else {
                // If the chat doesn't exist or the user isn't authorized, reset the selection
                $selectedChat = null;
            }
        }

        // Render the chat interface
        return $this->render('chat/index.html.twig', [
            'chats' => $chats,
            'selectedChat' => $selectedChat,
            'messages' => $messages,
            'form' => $formView,
        ]);
    }
}