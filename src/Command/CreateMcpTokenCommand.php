<?php
// src/Command/CreateMcpTokenCommand.php

namespace App\Command;

use App\Entity\McpToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erstellt einen langlebigen MCP-Token für einen Benutzer.
 *
 * Beispiel:
 *   php bin/console app:mcp:create-token info@jenssmit.de --name=Vibe
 */
#[AsCommand(
    name: 'app:mcp:create-token',
    description: 'Erstellt einen MCP-API-Token (Bearer) für die Anbindung von MCP-Clients (z.B. Vibe/Mistral).'
)]
class CreateMcpTokenCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-Mail-Adresse des Benutzers')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Bezeichnung des Clients (z.B. "Vibe")', 'default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $name = (string) $input->getOption('name');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('Benutzer mit E-Mail "%s" nicht gefunden.', $email));

            return Command::FAILURE;
        }

        $plainToken = McpToken::generateTokenValue();

        $mcpToken = new McpToken();
        $mcpToken->setUser($user);
        $mcpToken->setName($name);
        $mcpToken->setTokenHash(McpToken::hashToken($plainToken));
        $mcpToken->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($mcpToken);
        $this->entityManager->flush();

        $io->success('MCP-Token erstellt.');
        $io->writeln([
            '',
            '  Token (nur jetzt sichtbar, bitte sicher speichern):',
            sprintf('  <comment>%s</comment>', $plainToken),
            '',
            '  Verwendung im Authorization-Header:',
            sprintf('  <comment>Authorization: Bearer %s</comment>', $plainToken),
            '',
            '  Widerrufen mit:',
            sprintf('  <comment>php bin/console app:mcp:revoke-token %s</comment>', $plainToken),
            '',
        ]);

        return Command::SUCCESS;
    }
}
