<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl = 'http://localhost:3000' // Konfigurierbar machen
    ) {}

    /**
     * Generiert einen Reset-Token und sendet ihn per E-Mail
     */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            // Aus Sicherheitsgründen keine Info ob User existiert
            return;
        }

        // Generiere sicheren Token
        $token = bin2hex(random_bytes(32));
        
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->em->flush();

        // E-Mail senden
        $this->sendResetEmail($user, $token);
    }

    /**
     * Setzt das Passwort mit einem gültigen Reset-Token zurück
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user) {
            throw new BadRequestHttpException('Ungültiger Reset-Token.');
        }

        if (!$user->isResetTokenValid()) {
            throw new BadRequestHttpException('Reset-Token ist abgelaufen.');
        }

        // Validiere Passwort
        if (strlen($newPassword) < 8) {
            throw new BadRequestHttpException('Passwort muss mindestens 8 Zeichen lang sein.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Token löschen
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->em->flush();
    }

    /**
     * Ändert das Passwort eines authentifizierten Benutzers
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        // Überprüfe aktuelles Passwort
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
          throw new BadRequestHttpException('Das aktuelle Passwort ist ungültig.');
        }

        // Validiere neues Passwort
        if (strlen($newPassword) < 8) {
            throw new BadRequestHttpException('Neues Passwort muss mindestens 8 Zeichen lang sein.');
        }

        if ($currentPassword === $newPassword) {
            throw new BadRequestHttpException('Neues Passwort muss sich vom alten unterscheiden.');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->em->flush();
    }

    private function sendResetEmail(User $user, string $token): void
    {
        $resetUrl = sprintf('%s/reset-password?token=%s', $this->frontendUrl, $token);

        $email = (new Email())
            ->from('webmaster@jenssmit.de')
            ->to($user->getEmail())
            ->subject('Passwort zurücksetzen')
            ->html(sprintf(
                '<p>Hallo,</p>
                <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt.</p>
                <p>Klicken Sie auf folgenden Link, um Ihr Passwort zurückzusetzen:</p>
                <p><a href="%s">Passwort zurücksetzen</a></p>
                <p>Dieser Link ist 1 Stunde gültig.</p>
                <p>Falls Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail.</p>',
                $resetUrl
            ));

        $this->mailer->send($email);
    }
}