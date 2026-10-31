<?php
// src/Repository/McpTokenRepository.php

namespace App\Repository;

use App\Entity\McpToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<McpToken>
 */
class McpTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, McpToken::class);
    }

    /**
     * Findet einen aktiven (nicht widerrufenen) Token anhand seines Hashes.
     */
    public function findActiveByHash(string $tokenHash): ?McpToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tokenHash = :hash')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Listet alle aktiven Tokens eines Benutzers (für Übersichtszwecke).
     *
     * @return McpToken[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
