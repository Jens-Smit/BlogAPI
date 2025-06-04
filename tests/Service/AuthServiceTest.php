<?php
namespace App\Tests\Service;

use App\DTO\RegisterRequestDTO;
use App\Entity\User;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthServiceTest extends TestCase
{
    public function testRegisterThrowsExceptionIfEmailExists(): void
    {
        $dto = new RegisterRequestDTO('existing@example.com', 'password123');

        // Repository-Mock
        $userRepo = $this->createMock(ObjectRepository::class);
        $userRepo->method('findOneBy')->willReturn(new User());

        // EntityManager-Mock
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);

        // PasswordHasher-Mock (irrelevant in diesem Testfall)
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $authService = new AuthService($em, $hasher);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('E-Mail ist bereits registriert.');

        $authService->register($dto);
    }

    public function testRegisterCreatesUserAndPersists(): void
    {
        $dto = new RegisterRequestDTO('new@example.com', 'password123');

        // Repository-Mock
        $userRepo = $this->createMock(ObjectRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);

        // EntityManager-Mock
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        // PasswordHasher-Mock
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashedpassword');

        $authService = new AuthService($em, $hasher);
        $authService->register($dto);

        // Keine Exception = Test erfolgreich
        $this->assertTrue(true);
    }
}
