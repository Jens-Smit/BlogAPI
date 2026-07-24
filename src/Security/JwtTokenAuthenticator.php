<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
// Wir benötigen dieses Interface, um die Methode start() hinzuzufügen,
// obwohl wir von AbstractAuthenticator erben.
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Psr\Log\LoggerInterface;

class JwtTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserProviderInterface $userProvider,
        private readonly LoggerInterface $logger
    ) {}

    public function supports(Request $request): ?bool
    {
        $accessTokenCookieName = 'BEARER';
        $hasCookie = $request->cookies->has($accessTokenCookieName);
        $cookieValue = $request->cookies->get($accessTokenCookieName);

        // DEBUG: Log cookie information
        $this->logger->debug('JWT Authenticator supports check', [
            'has_cookie' => $hasCookie,
            'cookie_value' => $hasCookie ? '[REDACTED]' : null,
            'cookie_is_empty' => empty($cookieValue),
            'request_uri' => $request->getUri(),
            'request_host' => $request->getHost(),
            'request_headers' => $request->headers->all(),
            'request_cookies' => array_keys($request->cookies->all())
        ]);

        // Wir prüfen, ob das Cookie existiert und nicht leer ist.
        return $hasCookie && !empty($cookieValue);
    }

    public function authenticate(Request $request): Passport
    {
        $accessTokenCookieName = 'BEARER';
        $token = $request->cookies->get($accessTokenCookieName);

        // DEBUG: Log authentication attempt
        $this->logger->debug('JWT Authenticator authenticate attempt', [
            'token_present' => $token !== null,
            'token_length' => $token ? strlen($token) : 0,
            'request_uri' => $request->getUri(),
            'request_method' => $request->getMethod()
        ]);

        // Die Prüfung ist redundant wegen supports(), dient aber als Sicherheitsnetz,
        // falls der Token null oder leer ist. Wir verwenden CustomUserMessageAuthenticationException
        // anstelle der generischen AuthenticationException für bessere Fehlermeldungen.
        if (!$token) {
             throw new CustomUserMessageAuthenticationException('Access Token Cookie nicht gefunden. Authentifizierung fehlgeschlagen.');
        }

        try {
            $payload = $this->jwtManager->parse($token);

            // DEBUG: Log successful token parsing
            $this->logger->debug('JWT Authenticator token parsed successfully', [
                'payload_username' => $payload['email'] ?? 'unknown',
                'payload_exp' => $payload['exp'] ?? 'unknown',
                'payload_iat' => $payload['iat'] ?? 'unknown'
            ]);

            if (!isset($payload['email'])) {
                throw new CustomUserMessageAuthenticationException('Ungültiger Token: Benutzername fehlt.');
            }

            return new SelfValidatingPassport(
                new UserBadge($payload['email'], function ($userIdentifier) {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                })
            );
        } catch (\Exception $e) {
            // DEBUG: Log token parsing failure
            $this->logger->debug('JWT Authenticator token parsing failed', [
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);

            // Fängt alle JWT-bezogenen Fehler (Signatur, Ablauf) ab
            throw new CustomUserMessageAuthenticationException('Ungültiger oder abgelaufener Token.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Null = weiter mit dem Request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // DEBUG: Log authentication failure
        $this->logger->debug('JWT Authenticator authentication failure', [
            'exception_message' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'request_uri' => $request->getUri(),
            'request_cookies' => array_keys($request->cookies->all()),
            'request_headers' => $request->headers->all()
        ]);

        // Diese Methode wird aufgerufen, wenn die AUTHENTICATE-Methode fehlschlägt.
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * DIES IST DIE FEHLENDE METHODE, DIE DEN 500-FEHLER BEHEBT!
     * Sie wird aufgerufen, wenn supports() false zurückgibt (also KEIN Token gesendet wurde)
     * und ein geschützter Endpunkt aufgerufen wird. Sie erzeugt den erwarteten 401-Response.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // DEBUG: Log start method call (no token present)
        $this->logger->debug('JWT Authenticator start method called', [
            'request_uri' => $request->getUri(),
            'request_method' => $request->getMethod(),
            'request_cookies' => array_keys($request->cookies->all()),
            'request_headers' => $request->headers->all(),
            'auth_exception' => $authException ? $authException->getMessage() : 'None'
        ]);

        return new JsonResponse([
            'error' => 'Authentifizierung erforderlich',
            'message' => 'Es wurde kein gültiges Autorisierungs-Cookie (BEARER) gefunden.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}