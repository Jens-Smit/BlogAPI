<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Erstellt die Tabelle mcp_token für die Authentifizierung von MCP-Clients.
 * Gespeichert wird ausschließlich ein SHA-256-Hash des Tokens.
 */
final class Version20261031120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the mcp_token table for MCP bearer token authentication.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE mcp_token (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                created_at DATETIME_IMMUTABLE NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                last_used_at DATETIME_IMMUTABLE DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                revoked_at DATETIME_IMMUTABLE DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_9C4A7B23A76ED395 (user_id),
                UNIQUE INDEX UNIQ_9C4A7B23489DB1FD (token_hash),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE mcp_token ADD CONSTRAINT FK_9C4A7B23A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mcp_token DROP FOREIGN KEY FK_9C4A7B23A76ED395');
        $this->addSql('DROP TABLE mcp_token');
    }
}
