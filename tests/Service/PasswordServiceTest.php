<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private PasswordService $passwordService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->passwordService = new PasswordService(
            $this->userRepository,
            $this->em,
            $this->passwordHasher,
            $this->mailer,
            'http://localhost:3000'
        );
    }

    public function testRequestPasswordResetWithValidEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashedpassword');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $this->em->expects($this->once())->method('flush');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->passwordService->requestPasswordReset('test@example.com');

        $this->assertNotNull($user->getResetToken());
        $this->assertNotNull($user->getResetTokenExpiresAt());
        $this->assertTrue($user->isResetTokenValid());
    }

    public function testRequestPasswordResetWithNonExistentEmail(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        $this->em->expects($this->never())->method('flush');
        $this->mailer->expects($this->never())->method('send');

        // Sollte keine Exception werfen (Sicherheit)
        $this->passwordService->requestPasswordReset('nonexistent@example.com');
    }

    public function testResetPasswordWithValidToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('oldhashedpassword');
        $user->setResetToken('validtoken123');
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['resetToken' => 'validtoken123'])
            ->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newPassword123')
            ->willReturn('newhashedpassword');

        $this->em->expects($this->once())->method('flush');

        $this->passwordService->resetPassword('validtoken123', 'newPassword123');

        $this->assertNull($user->getResetToken());
        $this->assertNull($user->getResetTokenExpiresAt());
        $this->assertEquals('newhashedpassword', $user->getPassword());
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Ungültiger Reset-Token.');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['resetToken' => 'invalidtoken'])
            ->willReturn(null);

        $this->passwordService->resetPassword('invalidtoken', 'newPassword123');
    }

    public function testResetPasswordWithExpiredToken(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Reset-Token ist abgelaufen.');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setResetToken('expiredtoken');
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('-1 hour'));

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['resetToken' => 'expiredtoken'])
            ->willReturn($user);

        $this->passwordService->resetPassword('expiredtoken', 'newPassword123');
    }

    public function testResetPasswordWithShortPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Passwort muss mindestens 8 Zeichen lang sein.');

        $user = new User();
        $user->setResetToken('validtoken');
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->passwordService->resetPassword('validtoken', 'short');
    }

    public function testChangePasswordWithValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('oldhashedpassword');

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'oldPassword123')
            ->willReturn(true);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newPassword123')
            ->willReturn('newhashedpassword');

        $this->em->expects($this->once())->method('flush');

        $this->passwordService->changePassword($user, 'oldPassword123', 'newPassword123');

        $this->assertEquals('newhashedpassword', $user->getPassword());
    }

    public function testChangePasswordWithInvalidCurrentPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Das aktuelle Passwort ist ungültig.');

        $user = new User();
        $user->setPassword('oldhashedpassword');

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'wrongPassword')
            ->willReturn(false);

        $this->passwordService->changePassword($user, 'wrongPassword', 'newPassword123');
    }

    public function testChangePasswordWithShortNewPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Neues Passwort muss mindestens 8 Zeichen lang sein.');

        $user = new User();

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->willReturn(true);

        $this->passwordService->changePassword($user, 'oldPassword123', 'short');
    }

    public function testChangePasswordWithSamePassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Neues Passwort muss sich vom alten unterscheiden.');

        $user = new User();

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->willReturn(true);

        $this->passwordService->changePassword($user, 'samePassword123', 'samePassword123');
    }
}