<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Psr\Log\LoggerInterface;

use OpenApi\Annotations as OA;

class ContactController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Kontaktformular absenden",
     *     tags={"Kontakt"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name", "email", "subject", "message"},
     *                 @OA\Property(property="name", type="string", example="Max Mustermann"),
     *                 @OA\Property(property="email", type="string", format="email", example="max@example.com"),
     *                 @OA\Property(property="subject", type="string", example="Frage zum Produkt"),
     *                 @OA\Property(property="message", type="string", example="Hallo, ich habe eine Frage...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kontaktanfrage erfolgreich versendet",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ihre Nachricht wurde erfolgreich gesendet.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validierungsfehler oder ungültige Daten",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validierungsfehler"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Schema(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Serverfehler beim Senden der E-Mail",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Beim Senden der Nachricht ist ein Fehler aufgetreten.")
     *         )
     *     )
     * )
     */
    #[Route('/api/contact', name: 'api_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request, MailerInterface $mailer, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON data received for contact form.');
            return new JsonResponse(['message' => 'Ungültige JSON-Daten.'], Response::HTTP_BAD_REQUEST);
        }

        $inputBag = new Assert\Collection([
            'name' => new Assert\NotBlank(['message' => 'Der Name darf nicht leer sein.']),
            'email' => [
                new Assert\NotBlank(['message' => 'Die E-Mail darf nicht leer sein.']),
                new Assert\Email(['message' => 'Die E-Mail-Adresse ist ungültig.']),
            ],
            'subject' => new Assert\NotBlank(['message' => 'Der Betreff darf nicht leer sein.']),
            'message' => new Assert\NotBlank(['message' => 'Die Nachricht darf nicht leer sein.']),
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

        $name = $data['name'];
        $email = $data['email'];
        $subject = $data['subject'];
        $messageContent = $data['message'];

        try {
            $emailMessage = (new Email())
                ->from($email)
                ->to('info@Jenssmit.de')
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
