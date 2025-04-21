<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Schema;
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

        // Check if the messages table exists, and create it if it doesn't
        $connection = $em->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager->tablesExist(['messages'])) {
            $schema = new Schema();
            $table = $schema->createTable('messages');
            $table->addColumn('id', 'bigint', ['autoincrement' => true]);
            $table->addColumn('dateEnvoi', 'datetime', ['precision' => 6, 'notnull' => false]);
            $table->addColumn('message', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('chat_id', 'bigint', ['notnull' => false]);
            $table->addColumn('expediteur_id', 'bigint', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['chat_id'], 'IDX_messages_chat_id');
            $table->addIndex(['expediteur_id'], 'IDX_messages_expediteur_id');

            // Execute the schema creation
            $queries = $schema->toSql($connection->getDatabasePlatform());
            foreach ($queries as $query) {
                $connection->executeStatement($query);
            }
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
                    // Ensure chat and expediteur are set
                    if (!$selectedChat) {
                        throw $this->createNotFoundException('Chat not found.');
                    }
                    if (!$user) {
                        throw $this->createAccessDeniedException('User not found.');
                    }

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