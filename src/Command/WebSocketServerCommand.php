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