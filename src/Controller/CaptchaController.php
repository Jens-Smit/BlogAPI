<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\CaptchaGeneratorService; // NEU: Den Service importieren

class CaptchaController extends AbstractController
{
    private CaptchaGeneratorService $captchaGeneratorService;

    // Konstruktor-Injektion des Services
    public function __construct(CaptchaGeneratorService $captchaGeneratorService)
    {
        $this->captchaGeneratorService = $captchaGeneratorService;
    }

    #[Route('/api/captcha/generate', name: 'api_captcha_generate', methods: ['GET'])]
    public function generateCaptcha(SessionInterface $session): JsonResponse
    {
        // Die gesamte Bildgenerierungslogik wurde in den Service ausgelagert
        $captchaData = $this->captchaGeneratorService->generateCaptchaImages();

        $captchaId = uniqid('captcha_');
        // Speichere die initialen Rotationen in der Session
        $session->set('captcha_initial_rotations_' . $captchaId, $captchaData['initialRotations']);

        return new JsonResponse([
            'captchaId' => $captchaId,
            'imageParts' => $captchaData['imageParts'],
            'initialRotations' => $captchaData['initialRotations'], // Sende die initialen Rotationen auch an das Frontend
        ]);
    }

    #[Route('/api/captcha/verify', name: 'api_captcha_verify', methods: ['POST'])]
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
            $rotationByClicks = $clicks * -$this->captchaGeneratorService::ROTATION_STEP;  // Nutze Konstante aus Service

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