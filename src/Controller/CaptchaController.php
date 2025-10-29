<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // Verwenden Sie den Attribute-Import
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\CaptchaGeneratorService;

// WICHTIG: Verwenden Sie den Attributes-Import für moderne Symfony/PHP
use OpenApi\Attributes as OA; 

class CaptchaController extends AbstractController
{
    private CaptchaGeneratorService $captchaGeneratorService;

    public function __construct(CaptchaGeneratorService $captchaGeneratorService)
    {
        $this->captchaGeneratorService = $captchaGeneratorService;
    }

    // --- GENERATE CAPTCHA ---

    #[Route('/api/captcha/generate', name: 'api_captcha_generate', methods: ['GET'])]
    #[OA\Get(
        path: "/api/captcha/generate",
        summary: "Neues CAPTCHA generieren",
        description: "Gibt ein neues rotierbares CAPTCHA mit mehreren Bildteilen und deren Anfangsrotation zurück.",
        tags: ["Captcha"],
        responses: [
            new OA\Response(
                response: 200,
                description: "CAPTCHA erfolgreich generiert",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "captchaId", type: "string", example: "captcha_64c3f88a9c43d"),
                        new OA\Property(
                            property: "imageParts",
                            type: "array",
                            items: new OA\Items(type: "string", format: "uri")
                        ),
                        new OA\Property(
                            property: "initialRotations",
                            type: "array",
                            items: new OA\Items(type: "integer", example: 90)
                        )
                    ]
                )
            )
        ]
    )]
    public function generateCaptcha(SessionInterface $session): JsonResponse
    {
        $captchaData = $this->captchaGeneratorService->generateCaptchaImages();

        $captchaId = uniqid('captcha_');
        $session->set('captcha_initial_rotations_' . $captchaId, $captchaData['initialRotations']);

        return new JsonResponse([
            'captchaId' => $captchaId,
            'imageParts' => $captchaData['imageParts'],
            'initialRotations' => $captchaData['initialRotations'],
        ]);
    }

    // --- VERIFY CAPTCHA ---
    
    #[Route('/api/captcha/verify', name: 'api_captcha_verify', methods: ['POST'])]
    #[OA\Post(
        path: "/api/captcha/verify",
        summary: "CAPTCHA-Lösung verifizieren",
        description: "Überprüft, ob der Benutzer alle Bildteile korrekt ausgerichtet hat (Rotation = 0°).",
        tags: ["Captcha"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["captchaId", "userClicks"],
                properties: [
                    new OA\Property(property: "captchaId", type: "string", example: "captcha_64c3f88a9c43d"),
                    new OA\Property(
                        property: "userClicks",
                        type: "array",
                        description: "Anzahl der Klicks/Rotationen für jedes Bildteil.",
                        items: new OA\Items(type: "integer", example: 2)
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "CAPTCHA korrekt gelöst",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "CAPTCHA erfolgreich gelöst.")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Ungültige Anfrage oder falsche CAPTCHA-Lösung",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Falsche CAPTCHA-Lösung.")
                    ]
                )
            )
        ]
    )]
    public function verifyCaptcha(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $captchaId = $data['captchaId'] ?? null;
        $userClicks = $data['userClicks'] ?? [];

        if (!$captchaId) {
            return new JsonResponse(['success' => false, 'message' => 'CAPTCHA ID fehlt.'], Response::HTTP_BAD_REQUEST);
        }

        $initialRotations = $session->get('captcha_initial_rotations_' . $captchaId);

        if (!$initialRotations) {
            return new JsonResponse(['success' => false, 'message' => 'CAPTCHA nicht gefunden oder abgelaufen.'], Response::HTTP_BAD_REQUEST);
        }

        if (count($userClicks) !== count($initialRotations) || count($initialRotations) !== $this->captchaGeneratorService::NUM_PARTS) { 
            return new JsonResponse(['success' => false, 'message' => 'Ungültige Anzahl von CAPTCHA-Teilen.'], Response::HTTP_BAD_REQUEST);
        }

        $isCorrect = true;
        foreach ($userClicks as $index => $clicks) {
            $initialAngle = $initialRotations[$index];
            $rotationByClicks = $clicks * -$this->captchaGeneratorService::ROTATION_STEP;

            $finalRotation = ($initialAngle + $rotationByClicks) % 360;
            if ($finalRotation < 0) {
                $finalRotation += 360;
            }

            if ($finalRotation !== 0) {
                $isCorrect = false;
                break;
            }
        }

        $session->remove('captcha_initial_rotations_' . $captchaId);

        if ($isCorrect) {
            return new JsonResponse(['success' => true, 'message' => 'CAPTCHA erfolgreich gelöst.']);
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Falsche CAPTCHA-Lösung. Bitte versuchen Sie es erneut.'], Response::HTTP_BAD_REQUEST);
        }
    }
}