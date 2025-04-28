<?php

namespace App\WebSocket; // Déclare l'espace de noms pour organiser le code

use Ratchet\MessageComponentInterface; // Importe l'interface pour un composant de messages WebSocket
use Ratchet\ConnectionInterface; // Importe l'interface pour gérer les connexions WebSocket
use SplObjectStorage; // Importe une classe pour stocker plusieurs objets (ici les connexions)

class ChatServer implements MessageComponentInterface // Crée une classe ChatServer qui gère le chat
{
    protected $clients; // Variable pour stocker tous les clients connectés

    public function __construct()
    {
        $this->clients = new SplObjectStorage(); // Initialise la liste des clients (vide au début)
    }

    public function onOpen(ConnectionInterface $conn) // Quand un client se connecte
    {
        $this->clients->attach($conn); // On ajoute ce client à la liste
        echo "New connection! ({$conn->resourceId})\n"; // Affiche un message dans la console avec l'ID du client
    }

    public function onMessage(ConnectionInterface $from, $msg) // Quand un client envoie un message
    {
        $data = json_decode($msg, true); // Convertit le message JSON en tableau PHP

        if (!$data || !isset($data['chatId'], $data['message'], $data['userId'])) { // Si le message est mal formé ou incomplet
            return; // On arrête ici, on fait rien
        }

        foreach ($this->clients as $client) { // On parcourt tous les clients connectés
            if ($client !== $from) { // Si ce n'est pas l'auteur du message
                $client->send(json_encode([ // On envoie le message au client
                    'chatId' => $data['chatId'], // On envoie aussi l'ID du chat
                    'userId' => $data['userId'], // On envoie l'ID de l'utilisateur
                    'message' => $data['message'], // On envoie le message
                    'date' => (new \DateTime())->format('d/m/Y H:i') // On ajoute la date et l'heure
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) // Quand un client se déconnecte
    {
        $this->clients->detach($conn); // On enlève ce client de la liste
        echo "Connection closed! ({$conn->resourceId})\n"; // Affiche un message dans la console
    }

    public function onError(ConnectionInterface $conn, \Exception $e) // S'il y a une erreur
    {
        echo "An error occurred: {$e->getMessage()}\n"; // Affiche l'erreur dans la console
        $conn->close(); // Ferme la connexion avec le client
    }
}
