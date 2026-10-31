<?php
// src/Entity/McpToken.php

namespace App\Entity;

use App\Repository\McpTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Repräsentiert einen langlebigen API-Token für die MCP-Anbindung.
 *
 * Der Token wird nur einmal im Klartext ausgegeben (beim Erstellen über die
 * Konsole). Gespeichert wird ausschließlich ein SHA-256-Hash, sodass ein
 * Datenbank-Dump keine verwendbaren Zugangsdaten preisgibt.
 */
#[ORM\Entity(repositoryClass: McpTokenRepository::class)]
#[ORM\Table(name: 'mcp_token')]
class McpToken
{
    public const TOKEN_PREFIX = 'mcp_';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Menschlich lesbarer Name (z.B. "Vibe", "Claude Desktop"). */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** SHA-256-Hash des Klartext-Tokens. */
    #[ORM\Column(length: 64, unique: true)]
    private ?string $tokenHash = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revoke(): static
    {
        $this->revokedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * Erzeugt einen neuen Klartext-Token und hinterlegt seinen Hash.
     *
     * @return string Der Klartext-Token (Präfix "mcp_", 48 Hex-Zeichen).
     */
    public static function generateTokenValue(): string
    {
        return self::TOKEN_PREFIX . bin2hex(random_bytes(24));
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
