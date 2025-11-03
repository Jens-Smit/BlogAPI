<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SecurityHeadersListener implements EventSubscriberInterface
{
    private string $appEnv;

    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // 1. HTTPS Redirect nur in Production
        if ('prod' === $this->appEnv && !$request->isSecure()) {
            $url = str_replace('http://', 'https://', $request->getUri());
            $response->setStatusCode(301);
            $response->headers->set('Location', $url);
            $event->setResponse($response);
            return;
        }

        // 2. Strict-Transport-Security (HSTS)
        // Browser wird gezwungen HTTPS zu verwenden für 1 Jahr
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // 3. Content-Security-Policy (CSP)
        // Verhindert XSS durch Limiting von Script-Sources
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self'; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
        );

        // 4. X-Content-Type-Options (MIME Sniffing Prevention)
        // Browser wird gezwungen Content-Type zu respektieren
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 5. X-Frame-Options (Clickjacking Prevention)
        // Verhindert dass Seite in iFrame eingebettet wird
        $response->headers->set('X-Frame-Options', 'DENY');

        // 6. X-XSS-Protection (Legacy Browser Support)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 7. Referrer-Policy
        // Kontrolliert welche Referrer-Info weitergegeben wird
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 8. Permissions-Policy (Feature Policy)
        // Deaktiviert gefährliche APIs
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // 9. Remove Server Info (Information Disclosure Prevention)
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
    }
}