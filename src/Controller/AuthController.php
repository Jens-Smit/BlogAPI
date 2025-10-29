<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\DTO\RegisterRequestDTO;
use App\Service\AuthService;
use App\Service\PasswordService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordService $passwordService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly EntityManagerInterface $em,
        
    ) {}

    #[Route("/api/login", name: "api_login", methods: ["POST"])]
    public function login(Request $request,RateLimiterFactoryInterface $loginLimiter): JsonResponse
    {
        // ✅ Rate Limiting basierend auf IP-Adresse
        $limiter = $loginLimiter->create($request->getClientIp());
        
        // Prüfen ob Limit erreicht
        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'error' => 'Zu viele Login-Versuche. Bitte versuchen Sie es später erneut.'
            ], 429);
        }
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'E-Mail und Passwort sind erforderlich.'], 400);
        }

        // Benutzer laden und Passwort prüfen
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Ungültige Anmeldedaten.'], 401);
        }

        // 1. ACCESS TOKEN generieren
        $accessToken = $this->jwtManager->create($user);
        
        // 2. REFRESH TOKEN generieren
        // ✅ Verwenden Sie den TTL-Wert aus der Konfiguration
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            604800 // 7 Tage (muss mit gesdinet config übereinstimmen)
        );
        
        // ✅ WICHTIG: Token in Datenbank speichern!
        $this->refreshTokenManager->save($refreshToken);

        // 3. HttpOnly Cookie für ACCESS TOKEN
        $accessTokenCookie = new Cookie(
            'BEARER',
            $accessToken,
            time() + 3600,    // 1 Stunde
            '/',
            null,
            false,            // secure: true in Produktion!
            true,             // httpOnly
            false,
            'lax'
        );

        // 4. HttpOnly Cookie für REFRESH TOKEN
        // ✅ Cookie-Name MUSS 'refresh_token' sein!
        $refreshTokenCookie = new Cookie(
            'refresh_token',                      // ✅ WICHTIG: Exakt dieser Name!
            $refreshToken->getRefreshToken(),     // Der Token-String
            time() + 604800,                      // 7 Tage
            '/',
            null,
            false,                                // secure: true in Produktion!
            true,                                 // httpOnly
            false,
            'lax'
        );

        // 5. Response mit beiden Cookies
        $response = new JsonResponse([
            'message' => 'Login erfolgreich.',
            'user' => ['email' => $user->getUserIdentifier()]
        ], 200);

        $response->headers->setCookie($accessTokenCookie);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }

    #[Route("/api/logout", name: "api_logout", methods: ["POST"])]
    public function logout(Request $request): JsonResponse
    {
        // Refresh Token aus Cookie holen
        $refreshTokenString = $request->cookies->get('refresh_token');
        
        if ($refreshTokenString) {
            $refreshToken = $this->refreshTokenManager->get($refreshTokenString);
            if ($refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }
        }

        $response = new JsonResponse(['message' => 'Logout erfolgreich.']);
        
        // Beide Cookies löschen
        $response->headers->setCookie(
            new Cookie('BEARER', '', time() - 3600, '/', null, false, true, false, 'lax')
        );
        $response->headers->setCookie(
            new Cookie('refresh_token', '', time() - 3600, '/', null, false, true, false, 'lax')
        );

        return $response;
    }

    #[Route('/api/register', name: 'user_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'E-Mail und Passwort sind erforderlich.'], 400);
        }

        if (strlen($data['password']) < 8) {
            return new JsonResponse(['error' => 'Passwort muss mindestens 8 Zeichen lang sein.'], 400);
        }

        $dto = new RegisterRequestDTO($data['email'], $data['password']);

        try {
            $this->authService->register($dto);
            return new JsonResponse(['message' => 'Benutzer erfolgreich registriert.'], 201);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Registrierung fehlgeschlagen'], 500);
        }
    }

    #[Route('/api/password/request-reset', name: 'password_request_reset', methods: ['POST'])]
    public function requestPasswordReset(Request $request,RateLimiterFactoryInterface $passwordResetLimiter): JsonResponse
    {
        // ✅ Rate Limiting für Password Reset
        $limiter = $passwordResetLimiter->create($request->getClientIp());
        
        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'error' => 'Zu viele Passwort-Reset-Anfragen. Bitte versuchen Sie es später erneut.'
            ], 429);
        }
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'])) {
            return new JsonResponse(['error' => 'E-Mail ist erforderlich.'], 400);
        }

        try {
            $this->passwordService->requestPasswordReset($data['email']);
        } catch (\Throwable $e) {
            // Fehler verschleiern aus Sicherheitsgründen
        }

        return new JsonResponse([
            'message' => 'Falls die E-Mail existiert, wurde eine Reset-E-Mail versendet.'
        ], 200);
    }

    #[Route('/api/password/reset', name: 'password_reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['token'], $data['newPassword'])) {
            return new JsonResponse(['error' => 'Token und neues Passwort sind erforderlich.'], 400);
        }

        try {
            $this->passwordService->resetPassword($data['token'], $data['newPassword']);
            return new JsonResponse(['message' => 'Passwort erfolgreich zurückgesetzt.'], 200);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Fehler beim Zurücksetzen des Passworts'], 500);
        }
    }

    #[Route('/api/password/change', name: 'password_change', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Nicht authentifiziert.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['currentPassword'], $data['newPassword'])) {
            return new JsonResponse(['error' => 'Aktuelles und neues Passwort sind erforderlich.'], 400);
        }

        try {
            $this->passwordService->changePassword($user, $data['currentPassword'], $data['newPassword']);
            return new JsonResponse(['message' => 'Passwort erfolgreich geändert.'], 200);
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Fehler beim Ändern des Passworts'], 500);
        }
    }
}