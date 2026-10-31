<?php
// src/Command/RevokeMcpTokenCommand.php

namespace App\Command;

use App\Entity\McpToken;
use App\Repository\McpTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Widerruft einen MCP-Token. Der Klartext-Token oder die interne ID kann
 * angegeben werden. Widerrufene Tokens werden sofort abgewiesen.
 *
 * Beispiel:
 *   php bin/console app:mcp:revoke-token mcp_abc123...
 */
#[AsCommand(
    name: 'app:mcp:revoke-token',
    description: 'Widerruft einen MCP-API-Token (per Klartext-Token oder ID).'
)]
class RevokeMcpTokenCommand extends Command
{
    public function __construct(
        private readonly McpTokenRepository $mcpTokenRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('token', InputArgument::REQUIRED, 'Klartext-Token (mcp_...) oder interne Token-ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $value = (string) $input->getArgument('token');

        $mcpToken = null;

        if (ctype_digit($value)) {
            $mcpToken = $this->mcpTokenRepository->find((int) $value);
        }

        if (!$mcpToken && str_starts_with($value, McpToken::TOKEN_PREFIX)) {
            $mcpToken = $this->mcpTokenRepository->findActiveByHash(McpToken::hashToken($value));
        }

        if (!$mcpToken) {
            $io->error('Token nicht gefunden (bereits widerrufen oder unbekannt).');

            return Command::FAILURE;
        }

        if ($mcpToken->isRevoked()) {
            $io->warning('Token ist bereits widerrufen.');

            return Command::SUCCESS;
        }

        $mcpToken->revoke();
        $this->entityManager->flush();

        $io->success(sprintf(
            'Token #%d ("%s") wurde widerrufen und ist ab sofort ungültig.',
            $mcpToken->getId(),
            $mcpToken->getName()
        ));

        return Command::SUCCESS;
    }
}
