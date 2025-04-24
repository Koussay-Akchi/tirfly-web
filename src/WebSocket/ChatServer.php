<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class ChatServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Parse the incoming message (expected JSON)
        $data = json_decode($msg, true);
        if (!$data || !isset($data['chatId'], $data['message'], $data['userId'])) {
            return;
        }

        // Broadcast the message to all clients in the same chat
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send(json_encode([
                    'chatId' => $data['chatId'],
                    'userId' => $data['userId'],
                    'message' => $data['message'],
                    'date' => (new \DateTime())->format('d/m/Y H:i')
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove the connection
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}