<?php

  namespace App\Command;

  use App\WebSocket\ChatServer;
  use Ratchet\Http\HttpServer;
  use Ratchet\Server\IoServer;
  use Ratchet\WebSocket\WsServer;
  use Symfony\Component\Console\Attribute\AsCommand;
  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Output\OutputInterface;
//Imagine que quelqu’un se connecte à ton site et envoie un message → ce serveur WebSocket reçoit ce message et le transmet aux autres utilisateurs en direct.
//🚀 Quand tu tapes cette commande :
//php bin/console app:websocket:server
//👉 Ça démarre un serveur qui écoute les messages des utilisateurs (comme dans un chat WhatsApp, Messenger, etc.)


  #[AsCommand(name: 'app:websocket:server')]
  class WebSocketServerCommand extends Command
  {
      protected static $defaultName = 'app:websocket:server';

      protected function configure(): void
      {
          $this
              ->setDescription('Starts the WebSocket server for real-time chat')
              ->setHelp('This command starts the WebSocket server to handle real-time chat messages.');
      }

      protected function execute(InputInterface $input, OutputInterface $output): int
      {
          $output->writeln('Starting WebSocket server on port 8081...');

          $server = IoServer::factory(
              new HttpServer(
                  new WsServer(
                      new ChatServer()
                  )
              ),
              8081 // Changed from 8080 to 8081
          );

          $server->run();

          return Command::SUCCESS;
      }
  }