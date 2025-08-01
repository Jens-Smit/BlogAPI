<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Wichtig: Für #[Route] Attribut
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface; // Für Logging hinzufügen

class ContactController extends AbstractController
{
    // Konstruktor, um Logger zu injizieren (optional, aber gute Praxis)
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/contact', name: 'api_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request, MailerInterface $mailer,  ValidatorInterface $validator): JsonResponse
    {
        // 1. Daten aus dem Request-Body holen
        $data = json_decode($request->getContent(), true);

        // Prüfen, ob die Daten korrekt empfangen wurden
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON data received for contact form.');
            return new JsonResponse(['message' => 'Ungültige JSON-Daten.'], Response::HTTP_BAD_REQUEST);
        }

        // 2. Daten validieren
        $inputBag = new Assert\Collection([
            'name' => new Assert\NotBlank(['message' => 'Der Name darf nicht leer sein.']),
            'email' => [
                new Assert\NotBlank(['message' => 'Die E-Mail darf nicht leer sein.']),
                new Assert\Email(['message' => 'Die E-Mail-Adresse ist ungültig.']),
            ],
            'subject' => new Assert\NotBlank(['message' => 'Der Betreff darf nicht leer sein.']),
            'message' => new Assert\NotBlank(['message' => 'Die Nachricht darf nicht leer sein.']),
            // WICHTIG: Kein CAPTCHA-Feld hier, da die Verifizierung bereits im Frontend erfolgt ist
        ]);

        $violations = $validator->validate($data, $inputBag);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            $this->logger->warning('Contact form validation failed: ' . json_encode($errors));
            return new JsonResponse(['message' => 'Validierungsfehler', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Daten aus dem validierten Array extrahieren
        $name = $data['name'];
        $email = $data['email'];
        $subject = $data['subject'];
        $messageContent = $data['message'];

        // 3. E-Mail senden (Beispiel-Implementierung)
        try {
            $emailMessage = (new Email())
                ->from($email) // Absender ist die E-Mail des Benutzers
                ->to('info@Jenssmit.de') // <-- ÄNDERN: Deine E-Mail-Adresse hier einfügen
                ->subject('Kontaktformular: ' . $subject)
                ->html(
                    '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>' .
                    '<p><strong>E-Mail:</strong> ' . htmlspecialchars($email) . '</p>' .
                    '<p><strong>Betreff:</strong> ' . htmlspecialchars($subject) . '</p>' .
                    '<p><strong>Nachricht:</strong><br>' . nl2br(htmlspecialchars($messageContent)) . '</p>'
                );

            $mailer->send($emailMessage);

            $this->logger->info('Kontaktformular erfolgreich gesendet von ' . $email);

            return new JsonResponse(['message' => 'Ihre Nachricht wurde erfolgreich gesendet.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Senden der Kontaktformular-E-Mail: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['message' => 'Beim Senden der Nachricht ist ein Fehler aufgetreten.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}