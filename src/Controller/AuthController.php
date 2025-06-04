<?php

namespace App\Controller;

use OpenApi\Annotations as OA;

use App\DTO\RegisterRequestDTO;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class AuthController extends AbstractController
{
    private AuthService $authService;

    public function __construct(
        AuthService $authService
    ) {
        $this->authService = $authService;
    }
    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login mit E-Mail und Passwort",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="geheimesPasswort123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="JWT Token bei erfolgreichem Login",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJh..."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Ungültige Anmeldedaten",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Authentication failed")
     *         )
     *     )
     * )
     * 
      * @Route("/login", name="docs_login", methods={"POST"})
     */
    public function loginDoc(): JsonResponse
    {
        // Diese Methode wird nie aufgerufen – sie dient nur der Swagger-Dokumentation
        return new JsonResponse([], 501);
    }
 /**
     * @OA\Post(
     *     path="/register",
     *     summary="Registriert einen neuen Benutzer",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="geheimesPasswort123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Benutzer erfolgreich registriert"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Fehler bei Registrierung (fehlende Felder oder Validierungsfehler)",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="E-Mail und Passwort sind erforderlich.")
     *         )
     *     )
     * )
     */
    #[Route('/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'E-Mail und Passwort sind erforderlich.'], 400);
        }

        $dto = new RegisterRequestDTO($data['email'], $data['password']);

        try {
            $this->authService->register($dto);
            return new JsonResponse(['message' => 'Benutzer erfolgreich registriert.'], 201);
        } catch (BadRequestHttpException $e) {
            // wenn E-Mail bereits existiert → 400
            return new JsonResponse(
                ['error' => $e->getMessage()],
                400
            );
        }catch (\Throwable $e) {
            // Optional: Logging hier statt dd() für produktiven Code
            return new JsonResponse(['error' => 'Registrierung fehlgeschlagen', 'message' => $e->getMessage()], 500);
        }
    }

    
}
