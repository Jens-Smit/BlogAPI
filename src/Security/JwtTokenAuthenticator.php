<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
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

class JwtTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserProviderInterface $userProvider
    ) {}

    public function supports(Request $request): ?bool
    {
       
        
        $accessTokenCookieName = 'BEARER';
        // Ansonsten prüfen wir, ob ein Token im Header vorhanden ist
        // Wir prüfen, ob das Cookie existiert und nicht leer ist.
        return $request->cookies->has($accessTokenCookieName) 
           && !empty($request->cookies->get($accessTokenCookieName));
    }

    

    public function authenticate(Request $request): Passport
    {
        // !!! ÄNDERUNG HIER !!!
        // Token nicht mehr aus dem Header, sondern aus dem Cookie lesen
        $accessTokenCookieName = 'BEARER'; 
        $token = $request->cookies->get($accessTokenCookieName);

        if (!$token) {
            // Obwohl supports() true geliefert hat, ist das Token hier leer.
            throw new AuthenticationException('Access Token Cookie nicht gefunden.');
        }

        try {
            // ... (der Rest der Logik bleibt gleich, um das Token zu parsen und den User zu laden)
            $payload = $this->jwtManager->parse($token);
            
            if (!isset($payload['username'])) {
                throw new AuthenticationException('Ungültiger Token');
            }

            // ... (Rückgabe des SelfValidatingPassport)
            return new SelfValidatingPassport(
                new UserBadge($payload['username'], function ($userIdentifier) {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Ungültiger oder abgelaufener Token');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Null = weiter mit dem Request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}