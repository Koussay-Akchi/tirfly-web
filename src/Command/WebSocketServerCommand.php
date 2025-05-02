<?php

namespace App\Command; // On précise où se trouve notre fichier (comme une adresse dans le projet)

// On importe les outils dont on a besoin pour notre serveur et notre commande
use App\WebSocket\ChatServer; // Notre classe qui gère les messages du chat
use Ratchet\Http\HttpServer; // Crée un serveur HTTP (permet aux gens de se connecter)
use Ratchet\Server\IoServer; // Crée un serveur basique qui écoute les connexions
use Ratchet\WebSocket\WsServer; // Permet de faire du WebSocket (discussion en temps réel)
use Symfony\Component\Console\Attribute\AsCommand; // Permet de créer une commande Symfony facilement
use Symfony\Component\Console\Command\Command; // Classe de base pour toutes les commandes Symfony
use Symfony\Component\Console\Input\InputInterface; // Permet de lire ce que l'utilisateur tape dans la console
use Symfony\Component\Console\Output\OutputInterface; // Permet d'afficher des messages dans la console

// 📋 Ce fichier sert à démarrer un serveur WebSocket
// 🚀 Quand tu tapes : php bin/console app:websocket:server
// 👉 Ça démarre un serveur qui écoute les messages des utilisateurs en direct

#[AsCommand(name: 'app:websocket:server')] // Déclare le nom de la commande (comment l'appeler dans le terminal)

class WebSocketServerCommand extends Command // On crée une commande Symfony spéciale
{
    protected static $defaultName = 'app:websocket:server'; // Nom officiel de la commande (utilisé par Symfony)

    protected function configure(): void // Cette fonction décrit notre commande
    {
        $this
            ->setDescription('Démarre le serveur WebSocket pour discuter en direct') // Petite explication rapide de la commande
            ->setHelp('Cette commande démarre un serveur qui permet aux utilisateurs de discuter en direct.'); // Aide plus complète si besoin
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // Cette fonction est appelée quand on lance la commande
    {
        $output->writeln('Serveur WebSocket en cours de démarrage sur le port 8081...'); // Affiche un message pour dire que le serveur démarre

        // 🛠️ On crée un serveur WebSocket en plusieurs couches :
        $server = IoServer::factory( // Crée un serveur général (qui écoute)
            new HttpServer( // Enveloppe le serveur WebSocket avec un serveur HTTP (permet de recevoir les connexions)
                new WsServer( // Crée un serveur WebSocket (permet de discuter en direct)
                    new ChatServer() // C'est notre propre serveur qui gère les utilisateurs et leurs messages
                )
            ),
            8081 // 🔥 Le serveur écoute sur le port 8081 (adresse locale : http://localhost:8081)
        );

        $server->run(); // 🚀 Le serveur commence à tourner : il attend que les gens se connectent

        return Command::SUCCESS; // Indique que la commande s'est bien terminée (pas d'erreur)
    }
}
