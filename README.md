# Symfony Blog API - Erweiterte Dokumentation

Eine produktionsreife Blog-API mit erweiterten Funktionen für Benutzerauthentifizierung, CAPTCHA-System, hierarchische Kategorien und umfassende Dateiverwaltung.

**Version:** 2.0.0  
**Status:** Production-Ready  
**Lizenz:** MIT

---

## 📋 Inhaltsverzeichnis

- [Features](#-features)
- [Technologien](#-technologien)
- [Installation](#-installation)
- [Konfiguration](#-konfiguration)
- [API-Dokumentation](#-api-dokumentation)
- [Architektur](#-architektur)
- [Code-Dokumentation](#-code-dokumentation)
- [Code-Review](#-code-review)
- [Security-Review](#-security-review)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Troubleshooting](#-troubleshooting)

---

## 🚀 Features

### Benutzer-Management
- ✅ Benutzerregistrierung mit E-Mail-Validierung
- ✅ JWT-basierte Authentifizierung mit Refresh-Tokens
- ✅ HttpOnly-Cookies für sichere Token-Verwaltung
- ✅ Passwort-Reset mit zeitlich begrenzten Tokens
- ✅ Passwort-Änderung für authentifizierte Benutzer
- ✅ Rate-Limiting für Login-Versuche (5/15min)

### Blog-System (Erweitert)
- ✅ CRUD-Operationen für Blog-Posts
- ✅ Hierarchische Kategorien (Multi-Level)
- ✅ Mehrfache Bild-Uploads pro Post
- ✅ Dynamisches Titel-Bild-Management
- ✅ Platzhalter-basierte Bild-Integration
- ✅ Autor-Zuordnung mit Berechtigungsprüfung
- ✅ Kategorie-Filtering für Posts
- ✅ Zirkelbezug-Prävention in Kategorien
- ✅ Zeitstempel für Erstellungs- und Änderungsdaten

### CAPTCHA-System (Interaktiv)
- ✅ Dynamisch generierte, rotierbare CAPTCHA-Bilder
- ✅ 4 unabhängige Bildteile mit verschiedenen Formen
- ✅ Zufällige Farben und Skalierungen
- ✅ 45°-Rotation pro Benutzerklick
- ✅ Session-basierte Validierung

### Weitere Features
- ✅ Kontaktformular mit E-Mail-Versand
- ✅ Umfassende API-Dokumentation (Swagger/OpenAPI 3.0)
- ✅ CORS-Unterstützung mit konfigurierbaren Origins
- ✅ Vollständige Test-Abdeckung (Unit + Integration)
- ✅ Strukturiertes File-Upload-Management
- ✅ Error-Handling mit aussagekräftigen Fehlermeldungen
- ✅ Datenbank-Migrations-System

---

## 🛠 Technologien

### Backend
- **PHP 8.2+** - Moderne Sprachfeatures
- **Symfony 7.3** - Stabiles Framework
- **Doctrine ORM 3.0+** - Datenbankabstraktion
- **Lexik JWT Bundle 3.0** - JWT-Token-Management
- **Gesdinet JWT Refresh Token** - Token-Refresh-Handling
- **Nelmio API Doc 4.38+** - Swagger/OpenAPI-Dokumentation
- **Nelmio CORS 2.5+** - CORS-Handling

### Datenbank
- **MySQL 8.0+** / **MariaDB 10.4+** (konfigurierbar)
- **Doctrine Migrations** - Schema-Verwaltung

### Testing
- **PHPUnit 9.6+** - Unit- und Integrationstests
- **Symfony Test Framework** - Funktionale Tests
- **Test-Datenbank** - Isolierte Test-Umgebung

### Development Tools
- **Composer** - Dependency Management
- **Symfony CLI** - Development Server
- **Docker** (optional) - Containerisierung

---

## 📦 Installation

### Voraussetzungen

```bash
# Mindestversionen
PHP 8.2+
Composer 2.x
MySQL 8.0+ oder MariaDB 10.4+
Git

# Optional
Docker & Docker Compose
Node.js 16+ (für Frontend-Integration)
```

### Schritt-für-Schritt Installation

#### 1. Repository klonen

```bash
git clone https://github.com/your-org/symfony-blog-api.git
cd symfony-blog-api
```

#### 2. Dependencies installieren

```bash
composer install
# Oder für Production:
composer install --no-dev --optimize-autoloader
```

#### 3. Umgebungsvariablen konfigurieren

```bash
cp .env .env.local
# Bearbeite .env.local mit deinen Einstellungen
nano .env.local
```

**Erforderliche Umgebungsvariablen:**

```bash
# Database
DATABASE_URL="mysql://username:password@127.0.0.1:3306/blog_db"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_secure_passphrase

# Mailer
MAILER_DSN=smtp://localhost:1025
CONTACT_FROM_EMAIL=noreply@example.com
CONTACT_TO_EMAIL=info@example.com

# Frontend URLs
FRONTEND_URL=http://localhost:3000
API_URL=http://localhost:8000
CORS_ALLOW_ORIGIN=http://localhost:3000
```

#### 4. Datenbank einrichten

```bash
# Datenbank erstellen
php bin/console doctrine:database:create

# Migrations ausführen
php bin/console doctrine:migrations:migrate

# Optional: Fixtures laden
php bin/console doctrine:fixtures:load
```

#### 5. JWT-Schlüssel generieren

```bash
php bin/console lexik:jwt:generate-keypair
```

#### 6. Upload-Verzeichnisse erstellen

```bash
mkdir -p public/uploads
chmod 755 public/uploads
chmod 755 public
```

#### 7. Development-Server starten

```bash
# Mit Symfony CLI
symfony server:start

# Oder mit PHP Built-in Server
php -S localhost:8000 -t public/
```

Öffne `http://localhost:8000/api/doc` um die API-Dokumentation zu sehen.

---

## ⚙️ Konfiguration

### Datenbank-Konfiguration

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        server_version: '8.0'
        charset: utf8mb4
```

### JWT-Konfiguration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%kernel.project_dir%/config/jwt/private.pem'
    public_key: '%kernel.project_dir%/config/jwt/public.pem'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600  # 1 Stunde
    token_extractors:
        cookie:
            enabled: true
            name: BEARER  # HttpOnly Cookie
```

### Refresh Token Konfiguration

```yaml
# config/packages/gesdinet_jwt_refresh_token.yaml
gesdinet_jwt_refresh_token:
    ttl: 604800  # 7 Tage
    ttl_update: true
    single_use: true
    cookie:
        enabled: true
        http_only: true
        same_site: lax
        secure: false  # true in Produktion!
```

### Rate Limiting

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        login:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'
        token_refresh:
            policy: 'sliding_window'
            limit: 20
            interval: '1 hour'
        password_reset:
            policy: 'sliding_window'
            limit: 3
            interval: '1 hour'
```

### CORS-Konfiguration

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_credentials: true
        allow_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization']
        max_age: 3600
```

---

## 📚 API-Dokumentation

### Interaktive Dokumentation

Die vollständige API-Dokumentation ist unter folgendem Link verfügbar:

```
http://localhost:8000/api/doc       # Swagger UI
http://localhost:8000/api/doc.json  # OpenAPI JSON Schema
```

### Authentication Flow

#### 1. Benutzer registrieren

```bash
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securePassword123"
}

Response (201 Created):
{
  "message": "Benutzer erfolgreich registriert."
}
```

#### 2. Login

```bash
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securePassword123"
}

Response (200 OK):
{
  "message": "Login erfolgreich.",
  "user": {
    "email": "user@example.com"
  }
}

# HttpOnly Cookies werden gesetzt:
# Set-Cookie: BEARER=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; SameSite=Lax
# Set-Cookie: refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; SameSite=Lax
```

#### 3. Token erneuern

```bash
POST /api/token/refresh
Cookie: refresh_token=...

Response (200 OK):
{
  "token": "new_jwt_token_here",
  "refresh_token_expiration": 1234567890
}
```

#### 4. Logout

```bash
POST /api/logout
Cookie: BEARER=...; refresh_token=...

Response (200 OK):
{
  "message": "Logout erfolgreich."
}
```

### Posts-Endpunkte

#### Alle Posts abrufen

```bash
GET /api/posts?categoryId=1
Authorization: Bearer <token>  # Optional

Response (200):
[
  {
    "id": 1,
    "title": "Mein erster Post",
    "content": "<img src=\"...\">\nInhalt",
    "titleImage": "title-abc123.jpg",
    "images": ["image-def456.jpg"],
    "author": {
      "id": 1,
      "email": "user@example.com"
    },
    "category": {
      "id": 1,
      "name": "Technologie"
    },
    "createdAt": "2024-01-15T10:30:00+00:00"
  }
]
```

#### Post erstellen (mit Bild-Upload)

```bash
POST /api/posts
Content-Type: multipart/form-data
Authorization: Bearer <token>

Form-Data:
- title: "Neuer Post"
- content: "Text mit [img1] Platzhalter"
- categoryId: 1
- titleImage: <file>
- images: [<file1>, <file2>]
- imageMap: {"img1": "image1.jpg"}

Response (201):
{
  "message": "Post erfolgreich erstellt",
  "id": 42
}
```

#### Post aktualisieren

```bash
POST /api/posts/{id}
Content-Type: application/json
Authorization: Bearer <token>

{
  "title": "Aktualisierter Titel",
  "content": "Neuer Inhalt",
  "categoryId": 2
}

Response (200):
{
  "message": "Post erfolgreich aktualisiert",
  "id": 42
}
```

#### Post löschen

```bash
DELETE /api/posts/{id}
Authorization: Bearer <token>

Response (200):
{
  "message": "Post gelöscht"
}
```

### Kategorie-Endpunkte

#### Kategorie-Baum abrufen

```bash
GET /api/categories/root/tree

Response (200):
[
  {
    "id": 1,
    "name": "Technologie",
    "parentId": null,
    "children": [
      {
        "id": 2,
        "name": "Web Development",
        "parentId": 1,
        "children": []
      }
    ],
    "postCount": 5
  }
]
```

#### Kategorie erstellen

```bash
POST /api/categories
Content-Type: application/json
Authorization: Bearer <token>

{
  "name": "Neue Kategorie",
  "parentId": null  // Optional
}

Response (201):
{
  "message": "Kategorie erfolgreich erstellt",
  "id": 3
}
```

### CAPTCHA-Endpunkte

#### CAPTCHA generieren

```bash
GET /api/captcha/generate

Response (200):
{
  "captchaId": "captcha_64c3f88a9c43d",
  "imageParts": [
    "data:image/png;base64,iVBORw0KGgo...",
    "data:image/png;base64,iVBORw0KGgo...",
    ...
  ],
  "initialRotations": [90, 180, 270, 0]
}
```

#### CAPTCHA verifizieren

```bash
POST /api/captcha/verify
Content-Type: application/json

{
  "captchaId": "captcha_64c3f88a9c43d",
  "userClicks": [2, 4, 0, 6]  // Klicks um zu 0° zu rotieren
}

Response (200):
{
  "success": true,
  "message": "CAPTCHA erfolgreich gelöst."
}
```

---

## 🏗 Architektur

### Projektstruktur

```
symfony-blog-api/
├── bin/
│   └── console              # Symfony CLI
├── config/
│   ├── packages/           # Bundle-Konfiguration
│   ├── routes/             # Routing
│   ├── jwt/                # JWT Schlüssel
│   └── services.yaml       # Service-Container
├── migrations/             # Doctrine Migrations
├── public/
│   ├── index.php          # Entry Point
│   ├── uploads/           # Benutzer-Uploads
│   └── .htaccess          # Apache-Konfiguration
├── src/
│   ├── Controller/        # API-Controller
│   ├── Entity/            # Doctrine-Entities
│   ├── Repository/        # Data Access Layer
│   ├── Service/           # Business-Logic
│   ├── DTO/               # Data Transfer Objects
│   ├── Security/          # JWT-Authenticator
│   └── Kernel.php         # App-Kernel
├── tests/
│   ├── Controller/        # Funktionale Tests
│   ├── Service/           # Unit Tests
│   └── bootstrap.php      # Test-Setup
├── translations/          # i18n-Dateien
├── var/
│   ├── cache/            # Cache-Verzeichnis
│   ├── log/              # Log-Dateien
│   └── sessions/         # Session-Daten
├── vendor/               # Dependencies
├── .env                  # Environment-Template
├── .env.test            # Test-Environment
├── composer.json        # Dependencies
├── composer.lock        # Locked Dependencies
├── phpunit.xml.dist     # PHPUnit-Konfiguration
└── README.md            # Diese Datei
```

### Design Pattern

#### 1. **MVC (Model-View-Controller)**
- **Controller:** API-Endpoints, HTTP-Handling
- **Entity:** Datenbank-Modelle
- **Repository:** Data Access Layer

#### 2. **DTO (Data Transfer Objects)**
- Entkopplung von API-Input und Entities
- Validierung auf DTO-Ebene
- Type-Safety

```php
// src/DTO/PostCreateDTO.php
class PostCreateDTO {
    public function __construct(
        public readonly string $title,
        public readonly ?string $content,
        public readonly ?UploadedFile $titleImage,
        public readonly array $images = [],
    ) {}
}
```

#### 3. **Service Layer**
- Geschäftslogik außerhalb von Controllern
- Wiederverwendbarkeit
- Testbarkeit

```php
// src/Service/PostService.php
class PostService {
    public function createPost(PostCreateDTO $dto, User $author): Post {
        // Validierung, Upload-Handling, DB-Persistierung
    }
}
```

#### 4. **Security Layer**
- JWT-basierte Authentifizierung
- Rate-Limiting für sensible Operationen
- Input-Validierung und Sanitization

---

## 📖 Code-Dokumentation

### AuthController

**Zweck:** Authentifizierung und Passwort-Management

```php
// src/Controller/AuthController.php

#[Route("/api/login", methods: ["POST"])]
public function login(Request $request, RateLimiterFactoryInterface $loginLimiter): JsonResponse
{
    // 1. Rate-Limiting prüfen (5 Versuche / 15 min)
    // 2. Email & Passwort validieren
    // 3. Benutzer suchen
    // 4. Passwort verifizieren
    // 5. Access & Refresh Token generieren
    // 6. HttpOnly Cookies setzen
    // 7. Response mit Benutzer-Info zurückgeben
}
```

**HttpOnly Cookies:** Schützen vor XSS-Attacken durch Nicht-Zugreifbarkeit von JavaScript.

```php
$accessTokenCookie = new Cookie(
    'BEARER',                    // Cookie-Name
    $accessToken,               // Token-Wert
    time() + 3600,             // Ablaufzeit (1h)
    '/',                        // Pfad
    null,                       // Domain
    false,                      // Nicht secure (dev)
    true,                       // HttpOnly ✅
    false,                      // Raw
    'lax'                       // SameSite
);
```

### PostController

**Zweck:** CRUD-Operationen für Blog-Posts

**Wichtige Operationen:**

#### Create (mit File-Upload)

```php
#[Route('/api/posts', name: 'create_post', methods: ['POST'])]
public function create(Request $request, PostService $postService, Security $security): JsonResponse
{
    // 1. Multipart-Form-Data parsen
    // 2. DTO konstruieren
    // 3. Service aufrufen (Validierung + Upload)
    // 4. Post mit Author speichern
    // 5. ID in Response zurückgeben
}
```

**File-Upload-Verarbeitung:**

```php
// src/Service/PostService.php
private function uploadFile(UploadedFile $file): string
{
    // 1. Dateiname sanitizer (Sluggify)
    // 2. Eindeutige ID anhängen (prevent collisions)
    // 3. Zielverzeichnis verschieben
    // 4. Errorchecking (Exceptions)
    // 5. Filename zurückgeben
}
```

#### Update (mit Berechtigungsprüfung)

```php
public function update(int $id, Request $request, PostService $postService, Security $security): JsonResponse
{
    $post = $em->getRepository(Post::class)->find($id);
    
    // ✅ Autorität-Check: Nur Autor oder Admin darf updaten
    if ($post->getAuthor() !== $security->getUser()) {
        return new JsonResponse(['error' => 'Keine Berechtigung'], 403);
    }
    
    // Service aufrufen...
}
```

#### Delete (mit File-Cleanup)

```php
public function deletePost(Post $post): void
{
    // 1. Titelbild löschen
    // 2. Alle associated images löschen
    // 3. Post aus DB entfernen
    // 4. Flush
}
```

### CategoryController

**Zweck:** Kategorien-Management mit hierarchischer Struktur

**Wichtige Features:**

#### Hierarchie-Navigation

```php
#[Route('/api/categories/{id}/tree', name: 'get_category_tree')]
public function getCategoryTree(string $id, EntityManagerInterface $em): JsonResponse
{
    // 1. Alle Kategorien laden (1 Query)
    // 2. Map konstruieren: id => [entity, children, parent_id]
    // 3. Rekursiv Baumstruktur bauen
    // 4. JSON mit Hierarchie zurückgeben
}
```

#### Zirkelbezug-Prävention

```php
private function hasCircularReference(Category $category, int $targetParentId): bool
{
    // Rekursiver Check: Ist targetParentId in der Nachfolgerkette?
    foreach ($category->getCategories() as $child) {
        if ($child->getId() === $targetParentId) {
            return true;  // ⚠️ Zirkelbezug!
        }
        if ($this->hasCircularReference($child, $targetParentId)) {
            return true;
        }
    }
    return false;
}
```

### CaptchaGeneratorService

**Zweck:** Dynamische CAPTCHA-Bildgenerierung

```php
// src/Service/CaptchaGeneratorService.php

public function generateCaptchaImages(): array
{
    // 1. Zufällige Form wählen (Kreis oder Quadrat)
    // 2. Zufällige Farbe wählen
    // 3. Für jedes der 4 Teile:
    //    - Separate GD-Image erstellen
    //    - Formteil zeichnen (Teil-Sektor)
    //    - Zentralen Marker-Punkt zeichnen
    //    - Trennlinien zeichnen
    //    - In Base64 konvertieren
    // 4. Zufällige Startrotationen generieren
    // 5. imageParts + initialRotations zurückgeben
}
```

**GD-Library verwendung:**

```php
// Bildteile zeichnen mit imagefilledarc (Bögen)
imagefilledarc(
    $partImage,
    $drawOriginX, $drawOriginY,
    $arcRadius * 2, $arcRadius * 2,
    $startAngle, $endAngle,
    $mainColor,
    IMG_ARC_PIE
);
```

### JwtTokenAuthenticator

**Zweck:** JWT-Token-Extraktion und Benutzer-Authentifizierung

```php
// src/Security/JwtTokenAuthenticator.php

public function authenticate(Request $request): Passport
{
    // 1. Token aus Cookie 'BEARER' extrahieren
    $token = $request->cookies->get('BEARER');
    
    // 2. Token parsen (JWT Manager)
    $payload = $this->jwtManager->parse($token);
    
    // 3. Username extrahieren
    $username = $payload['username'];
    
    // 4. Benutzer laden
    $user = $this->userProvider->loadUserByIdentifier($username);
    
    // 5. Passport zurückgeben (wird von Symfony validiert)
    return new SelfValidatingPassport(
        new UserBadge($username, fn() => $user)
    );
}
```

---

## 🔍 Code-Review

### 1. **Authentication & Security**

#### ✅ Stärken:

- **HttpOnly Cookies:** XSS-Protection durch Nicht-Zugreifbarkeit von JS
- **JWT mit RS256:** Asymmetrische Signatur (sicherer als HS256)
- **Refresh Token Separation:** Access-Token kurz (1h), Refresh-Token lang (7d)
- **Single-Use Refresh Tokens:** Verhindert Token-Reuse nach Refresh
- **Rate-Limiting:** 5 Login-Versuche / 15 Minuten

#### ⚠️ Observations:

```php
// ⚠️ Beobachtung 1: HTTPS nicht erzwungen
// In Production sollte Secure-Flag gesetzt sein:
$accessTokenCookie = new Cookie(
    'BEARER',
    $accessToken,
    time() + 3600,
    '/',
    null,
    false,  // ⚠️ Sollte TRUE in Production sein!
    true,
    false,
    'lax'
);

// ✅ Empfehlung:
// config/.env.prod.local
SECURE_COOKIES=true  // Dann in Controller verwenden
```

#### 🔧 Recommendations:

```php
// 1. CORS-Origin validieren (nicht per Wildcard)
CORS_ALLOW_ORIGIN=https://yourdomain.com  // Nicht http://

// 2. Cookie-Secure-Flag in Production
if ('prod' === $_ENV['APP_ENV']) {
    $cookie->withSecure(true);
}

// 3. CSRF-Protection für State-Changing Operations
// config/packages/csrf.yaml - bereits implementiert ✅
```

### 2. **Input Validation & Sanitization**

#### ✅ Stärken:

- **DTO mit Symfony Validator Constraints**
- **Email-Validierung**
- **Passwort-Mindestlänge (8 Zeichen)**
- **HTML-Escaping mit htmlspecialchars()**

```php
// src/Controller/ContactController.php
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');  // ✅ XSS-Protection
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
```

#### ⚠️ Issues:

```php
// ⚠️ Fehlende MIME-Type Validierung
$file = $request->files->get('file');

// ❌ Nur guessExtension() ist nicht sicher genug!
// Attacker könnte .php.jpg hochladen
$extension = $file->guessExtension();

// ✅ Empfehlung: Strict MIME-Type Check
$mimeType = $file->getMimeType();
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mimeType, $allowedMimes)) {
    throw new BadRequestHttpException('Dateityp nicht erlaubt');
}

// ⚠️ Auch: Dateigröße nicht limitiert
// ✅ Sollte in Upload-Handler sein:
if ($file->getSize() > 5242880) {  // 5MB
    throw new BadRequestHttpException('Datei zu groß');
}
```

#### 🔧 Security Improvements:

```php
// 1. Whitelist erlaubter Dateitypen
private const ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];

private const MAX_FILE_SIZE = 5242880;  // 5MB

private function validateAndUploadFile(UploadedFile $file): string
{
    // Prüfe MIME-Type
    $mimeType = $file->getMimeType();
    if (!isset(self::ALLOWED_MIMES[$mimeType])) {
        throw new BadRequestHttpException('Dateityp nicht erlaubt');
    }
    
    // Prüfe Dateigröße
    if ($file->getSize() > self::MAX_FILE_SIZE) {
        throw new BadRequestHttpException('Datei überschreitet 5MB Limit');
    }
    
    // Prüfe mit finfo (zusätzliche Validierung)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeByContent = finfo_file($finfo, $file->getRealPath());
    finfo_close($finfo);
    
    if ($mimeByContent !== $mimeType) {
        throw new BadRequestHttpException('Dateiinhalt stimmt nicht überein');
    }
    
    // Prüfe nicht: ImageGetImageType für echte Validierung
    if (!getimagesize($file->getRealPath())) {
        throw new BadRequestHttpException('Ungültiges Bild');
    }
    
    // Jetzt sicher uploaden...
    return $this->moveToUploadDirectory($file);
}

// 2. Upload-Verzeichnis nicht webzugänglich machen
// .htaccess in public/uploads/
<FilesMatch "\.(php|php3|php4|php5|phtml|pht|shtml)$">
    Order allow,deny
    Deny from all
</FilesMatch>

// Oder: .htaccess in uploads/
<Files *>
    deny from all
</Files>

// 3. Symlinks für Download via PHP
// .env
UPLOAD_DIRECTORY=/var/www/blog/storage/uploads  # Außerhalb public/

// Controller: Datei via X-Sendfile header ausliefern
header('X-Sendfile: ' . $filePath);
header('Content-Type: ' . $mimeType);
```

### 5. **Error Handling & Logging**

#### ✅ Stärken:

- **Custom Exception Handling**
- **Aussagekräftige Error Messages für Clients**
- **Monolog Integration für Logging**

```php
// src/Controller/PostController.php
try {
    $post = $postService->createPost($dto, $security->getUser());
    return new JsonResponse(['message' => 'Post erstellt', 'id' => $post->getId()], 201);
} catch (\Throwable $e) {
    error_log('Fehler beim Erstellen des Posts: ' . $e->getMessage());
    return new JsonResponse(['error' => 'Fehler beim Erstellen'], 500);
}
```

#### ⚠️ Issues:

```php
// ⚠️ Zu viel Info in Error Messages (Information Disclosure)
catch (\Exception $e) {
    // ❌ Gibt Exception-Message an Client!
    return new JsonResponse(['error' => $e->getMessage()], 500);
}

// ✅ Besser: Generische Messages + Logging
catch (\Exception $e) {
    $this->logger->error('Post creation failed', [
        'exception' => $e,
        'userId' => $user->getId()
    ]);
    
    return new JsonResponse([
        'error' => 'Ein Fehler ist aufgetreten'
    ], 500);  // Keine Details dem Client zeigen
}
```

#### 🔧 Improvements:

```php
// 1. Custom Exception Classes
namespace App\Exception;

class PostNotFoundException extends \Exception {}
class UnauthorizedPostAccessException extends \Exception {}

// 2. Error Handler mit Status Codes
class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onKernelException',
        ];
    }
    
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof PostNotFoundException) {
            $response = new JsonResponse(['error' => 'Post nicht gefunden'], 404);
        } elseif ($exception instanceof UnauthorizedPostAccessException) {
            $response = new JsonResponse(['error' => 'Keine Berechtigung'], 403);
        } else {
            $response = new JsonResponse(['error' => 'Interner Fehler'], 500);
        }
        
        $event->setResponse($response);
    }
}

// 3. Structured Logging
$this->logger->info('Post created', [
    'postId' => $post->getId(),
    'userId' => $user->getId(),
    'categoryId' => $post->getCategory()->getId(),
    'timestamp' => date('c')
]);
```

### 6. **Authorization & Access Control**

#### ✅ Stärken:

- **Role-based Access Control (RBAC)**
- **Ownership Checks** (User darf nur eigene Posts ändern)
- **Firewall-basierte Route-Protection**

```php
// config/packages/security.yaml
access_control:
    - { path: ^/api/posts$, roles: PUBLIC_ACCESS, methods: [GET] }
    - { path: ^/api/posts, roles: IS_AUTHENTICATED_FULLY, methods: [POST] }
    - { path: ^/api/login$, roles: PUBLIC_ACCESS }
```

```php
// src/Controller/PostController.php
if ($post->getAuthor() !== $security->getUser()) {
    return new JsonResponse(['error' => 'Keine Berechtigung'], 403);
}
```

#### ⚠️ Issues:

```php
// ⚠️ Timing Attack möglich bei Passwort-Check
if (!$this->passwordHasher->isPasswordValid($user, $password)) {
    // ❌ Unterschiedliche Response-Zeiten je nach ob User existiert!
    return new JsonResponse(['error' => 'Benutzer nicht gefunden'], 401);
}

// ✅ Besser: Konstante Response-Zeit
$user = $this->userRepository->findOneBy(['email' => $email]);
$isValid = $user && $this->passwordHasher->isPasswordValid($user, $password);

if (!$isValid) {
    // Gleiche Zeit, egal ob User existiert oder nicht
    return new JsonResponse(['error' => 'Ungültige Anmeldedaten'], 401);
}
```

#### 🔧 Recommendations:

```php
// 1. Permission-basierte Prüfung statt Role-basierten
// Symfony Voters verwenden
namespace App\Security\Voter;

class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'EDIT' && $subject instanceof Post;
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if ($attribute === 'EDIT') {
            return $subject->getAuthor() === $user || $this->isAdmin($user);
        }
        
        return false;
    }
}

// Im Controller verwenden:
$this->denyAccessUnlessGranted('EDIT', $post);

// 2. Audit-Logging für sensitive Operationen
$this->auditLogger->log([
    'action' => 'POST_UPDATED',
    'postId' => $post->getId(),
    'userId' => $user->getId(),
    'changes' => $differences,
    'timestamp' => new \DateTime(),
    'ipAddress' => $request->getClientIp()
]);
```

---

## 🛡️ Security-Review

### 1. **Authentication Mechanisms**

| Aspekt | Status | Details |
|--------|--------|---------|
| JWT-Token | ✅ | RS256 (asymmetrisch), 1h TTL |
| Refresh-Token | ✅ | 7 Tage TTL, Single-Use |
| HttpOnly Cookies | ✅ | XSS-Protection |
| SameSite-Cookie | ✅ | Lax-Mode gesetzt |
| HTTPS | ⚠️ | Nur in Production |
| Password Hashing | ✅ | Bcrypt/Argon2 via Symfony |

### 2. **Authorization & Access Control**

| Aspekt | Status | Details |
|--------|--------|---------|
| Role-based Access | ✅ | Firewall-basiert |
| Ownership-Checks | ✅ | Users dürfen nur eigene Posts ändern |
| Resource-Level Auth | ⚠️ | Nur auf Post-Level, nicht auf File-Level |
| API-Key Auth | ❌ | Nicht implementiert |
| OAuth2 | ❌ | Nicht implementiert |

### 3. **Input Validation**

| Aspekt | Status | Details |
|--------|--------|---------|
| Email-Validierung | ✅ | Symfony Validator |
| Passwort-Validierung | ✅ | Min 8 Zeichen |
| File-Type Validierung | ⚠️ | Nur guessExtension(), kein MIME-Check |
| File-Size Limits | ❌ | Nicht implementiert |
| HTML-Escaping | ✅ | htmlspecialchars() in ContactController |
| SQL-Injection | ✅ | Doctrine ORM Prepared Statements |
| CORS-Validierung | ✅ | Whitelist in config |

### 4. **Cryptography & Secrets**

| Aspekt | Status | Details |
|--------|--------|---------|
| JWT Secret-Key | ✅ | Privat in .pem File |
| Password Hashing | ✅ | Bcrypt/Argon2 |
| HTTPS/TLS | ⚠️ | Nur in Production |
| Secrets Management | ⚠️ | .env in .gitignore, aber keine Vault |
| Passphrase | ⚠️ | In .env gespeichert |

### 5. **Rate Limiting**

| Aspekt | Status | Details |
|--------|--------|---------|
| Login Rate Limit | ✅ | 5 Versuche / 15 min |
| Password Reset Limit | ✅ | 3 Versuche / Stunde |
| Token Refresh Limit | ✅ | 20 Versuche / Stunde |
| API Rate Limit | ✅ | 100 Requests / Minute |
| File Upload Limit | ❌ | Nicht implementiert |
| CAPTCHA Limit | ❌ | Nicht implementiert |

### 6. **Data Protection**

| Aspekt | Status | Details |
|--------|--------|---------|
| Password Reset Token | ✅ | 1 Stunde TTL, Token-gehashed |
| GDPR Compliance | ⚠️ | Teilweise (Datenexport fehlt) |
| PII-Handling | ⚠️ | Keine Encryption at Rest |
| Audit Logging | ⚠️ | Basis-Logging vorhanden |
| Secure Deletion | ❌ | Files nicht sicher gelöscht |

### 7. **OWASP Top 10 - Compliance**

| OWASP Issue | Status | Mitigations |
|-------------|--------|------------|
| A01:2021 - Broken Access Control | ✅ | RBAC, Ownership-Checks, CORS |
| A02:2021 - Cryptographic Failures | ✅ | HTTPS (prod), bcrypt, JWT RS256 |
| A03:2021 - Injection | ✅ | Doctrine ORM, Prepared Statements |
| A04:2021 - Insecure Design | ⚠️ | Grundlagen OK, aber keine Threat-Modeling |
| A05:2021 - Security Misconfiguration | ✅ | .env-basiert, Migrations |
| A06:2021 - Vulnerable Components | ⚠️ | Regelmäßige Updates nötig |
| A07:2021 - Identification & Auth Failures | ✅ | JWT, Password hashing, Rate limiting |
| A08:2021 - Data Integrity Failures | ⚠️ | Keine Request-Signatur |
| A09:2021 - Logging & Monitoring | ⚠️ | Basic logging, kein Alerting |
| A10:2021 - SSRF | ✅ | Keine externe URL-Requests |

### 8. **Security Checklist - TODO**

#### High Priority (Vor Production)

- [ ] HTTPS erzwingen (redirect http -> https)
- [ ] Secure-Cookie Flag in Production setzen
- [ ] File-Upload MIME-Type validieren
- [ ] File-Size Limits einführen
- [ ] Content-Security-Policy Header hinzufügen
- [ ] X-Frame-Options Header hinzufügen
- [ ] Strict-Transport-Security Header hinzufügen

```php
// config/routes.yaml - Middleware hinzufügen
#[Route(..., methods: ['GET'])]
#[IsGranted('PUBLIC_ACCESS')]
public function getPost(Request $request): Response
{
    // Response-Header setzen
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'");
    
    return $response;
}
```

#### Medium Priority (Within 1 Month)

- [ ] API-Versioning einführen (/api/v1/posts)
- [ ] Request-Signing implementieren
- [ ] Audit-Logging für alle kritischen Operationen
- [ ] Web Application Firewall (WAF) konfigurieren
- [ ] Secrets Management (Vault/AWS Secrets) setup
- [ ] Dependency Security Scanning (composer audit)
- [ ] GDPR Datenexport-Endpoint

#### Low Priority (Nice to Have)

- [ ] OAuth2/OIDC Support
- [ ] API-Key Authentication als Alternative
- [ ] DDoS Protection (Cloudflare)
- [ ] Incident Response Plan
- [ ] Security Policy (SECURITY.md)
- [ ] Bug Bounty Program

---

## 🧪 Testing

### Test-Setup

```bash
# Test-Datenbank vorbereiten
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Alle Tests ausführen
php bin/phpunit

# Spezifische Test-Suite
php bin/phpunit tests/Controller/AuthControllerTest.php
php bin/phpunit tests/Service/PostServiceTest.php

# Mit Coverage
php bin/phpunit --coverage-html coverage/
```

### Test-Beispiele

#### Unit Test - Service

```php
// tests/Service/PostServiceTest.php
class PostServiceTest extends TestCase
{
    public function testCreatePostWithValidData(): void
    {
        // Arrange
        $uploadedFile = $this->createMockUploadedFile('test.jpg');
        $author = new User();
        
        // Act
        $post = $this->postService->createPost($dto, $author);
        
        // Assert
        $this->assertNotNull($post->getId());
        $this->assertEquals('Test Title', $post->getTitle());
    }
}
```

#### Integration Test - Controller

```php
// tests/Controller/PostControllerTest.php
class PostControllerTest extends WebTestCase
{
    public function testCreatePostWithMultipleImages(): void
    {
        // Arrange
        $user = $this->createAndPersistUser();
        $this->client->loginUser($user);
        
        // Act
        $this->client->request('POST', '/api/posts', 
            ['title' => 'Test'],
            ['titleImage' => $titleFile, 'images' => [$img1, $img2]]
        );
        
        // Assert
        $this->assertResponseStatusCodeSame(201);
    }
}
```

### Test Coverage Ziele

- **Unit Tests:** > 80% Abdeckung für Services
- **Integration Tests:** Alle API-Endpoints
- **Functional Tests:** Critical User Paths (Register -> Login -> Create Post -> Update -> Delete)

---

## 🚀 Deployment

### Development Setup

```bash
# 1. Clone & Install
git clone https://github.com/org/symfony-blog-api.git
cd symfony-blog-api
composer install

# 2. Environment
cp .env .env.local
# Bearbeite .env.local mit deinen Daten

# 3. Database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 4. Keys
php bin/console lexik:jwt:generate-keypair

# 5. Server
symfony server:start
```

### Production Deployment

#### Via Docker

```dockerfile
# Dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git composer \
    mysql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/ public/

CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "9000:9000"
    volumes:
      - .:/app
    environment:
      DATABASE_URL: mysql://user:pass@db:3306/blog_prod
      JWT_PASSPHRASE: ${JWT_PASSPHRASE}
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: blog_prod
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

#### Via Systemd

```bash
# /etc/systemd/system/blog-api.service
[Unit]
Description=Symfony Blog API
After=network.target

[Service]
Type=notify
ExecStart=/usr/bin/php-fpm
User=www-data
WorkingDirectory=/var/www/blog-api

[Install]
WantedBy=multi-user.target
```

#### Nginx Configuration

```nginx
# /etc/nginx/sites-available/blog-api
upstream php_backend {
    server 127.0.0.1:9000;
}

server {
    listen 80;
    server_name api.example.com;
    
    # HTTPS redirect
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.example.com;
    
    ssl_certificate /etc/letsencrypt/live/api.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.example.com/privkey.pem;
    
    root /var/www/blog-api/public;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass php_backend;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
    
    location ~ \.php$ {
        return 404;
    }
    
    location ~ ^/uploads/ {
        # Protect uploads from execution
        location ~ \.php$ {
            return 403;
        }
    }
}
```

### Environment Variables für Production

```bash
# .env.prod.local
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="mysql://user:pass@db-prod.example.com:3306/blog_prod"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=super_secret_passphrase
MAILER_DSN=smtp://smtp.sendgrid.net:587?encryption=tls&username=apikey&password=SG.XXX
FRONTEND_URL=https://example.com
API_URL=https://api.example.com
CORS_ALLOW_ORIGIN=https://example.com
SECURE_COOKIES=true
HTTPS_ONLY=true
```

### Pre-Deployment Checklist

- [ ] Alle Tests grün (php bin/phpunit)
- [ ] HTTPS Certificate vorhanden
- [ ] Datenbank Backup erstellt
- [ ] Environment-Variablen konfiguriert
- [ ] JWT-Schlüssel generiert
- [ ] Upload-Verzeichnis erstellt & konfiguriert
- [ ] Security Headers konfiguriert
- [ ] Rate-Limiting getestet
- [ ] Error-Logging konfiguriert
- [ ] Monitoring/Alerting setup
- [ ] CORS-Origins whitelist korrekt

---

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. "JWT Token nicht gefunden"

```bash
# Problem: HttpOnly Cookie wird nicht vom Client gesendet

# Lösung 1: HTTPS (in Production)
SECURE_COOKIES=true

# Lösung 2: SameSite-Cookie in Test-Kontext
# .env.test
SYMFONY_BROWSER_TRUSTED_HOSTS=localhost

# Lösung 3: Test-Code anpassen
$this->client->getCookieJar()->set($browserKitCookie);
```

#### 2. "N+1 Query Problem"

```bash
# Problem: Zu viele Datenbankqueries

# Lösung: JOIN in Query verwenden
$qb = $em->getRepository(Post::class)->createQueryBuilder('p');
$posts = $qb
    ->leftJoin('p.author', 'a')
    ->addSelect('a')
    ->leftJoin('p.category', 'c')
    ->addSelect('c')
    ->getQuery()
    ->getResult();

# Debugging: Profiler nutzen
# http://localhost:8000/_profiler
```

#### 3. "File Upload fehlgeschlagen"

```bash
# Problem: Permissions oder Verzeichnis nicht vorhanden

# Lösung:
mkdir -p public/uploads
chmod 755 public/uploads
chown www-data:www-data public/uploads

# Debug:
ls -la public/uploads/
php -r "echo is_writable('public/uploads') ? 'writable' : 'not writable';"
```

#### 4. "CORS Error"

```bash
# Problem: Origin nicht in Whitelist

# Lösung: .env.local überprüfen
CORS_ALLOW_ORIGIN=https://yourdomain.com

# Dann Nginx/Apache Cache clearen
php bin/console cache:clear
```

---

## 📊 Performance Optimization

### Caching Strategies

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: redis://localhost:6379
```

### Database Query Optimization

```php
// Eager Loading
$posts = $em->getRepository(Post::class)
    ->createQueryBuilder('p')
    ->leftJoin('p.author', 'a')
    ->addSelect('a')
    ->leftJoin('p.category', 'c')
    ->addSelect('c')
    ->getQuery()
    ->getResult();

// Pagination
$posts = $qb
    ->setFirstResult(($page - 1) * $limit)
    ->setMaxResults($limit)
    ->getQuery()
    ->getResult();
```

---

## 🤝 Contributing

Contributions sind willkommen! Bitte beachte:

1. Fork des Projekts
2. Feature Branch (`git checkout -b feature/amazing-feature`)
3. Code-Style: PSR-12
4. Tests schreiben (`php bin/phpunit`)
5. Commit (`git commit -m 'Add amazing feature'`)
6. Push (`git push origin feature/amazing-feature`)
7. Pull Request erstellen

---

## 📝 Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert. Siehe [LICENSE](LICENSE) für Details.

---

## 📞 Support & Community

- **Issues:** [GitHub Issues](https://github.com/org/symfony-blog-api/issues)
- **Email:** support@example.com
- **Documentation:** [Full Docs](https://docs.example.com)
- **Community Chat:** [Discord](https://discord.example.com)

---

**Zuletzt aktualisiert:** 2024-01-15  
**Entwickler:** Jens Smit  
**Repository:** https://github.com/Jens-Smit/symfony-blog-api️ Potential Issue: PostUpdateDTO
public function update(int $id, Request $request, PostService $postService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    // ⚠️ Keine Validierung des User-Inputs!
    $title = $data['title'] ?? null;
    $content = $data['content'] ?? null;  // Kann HTML sein!
    
    // ❌ Content wird direkt in Post-Entity gespeichert
    $post->setContent($content);
}

// ✅ Empfehlung: Content-Security-Policy Header
// config/packages/framework.yaml
framework:
    http_client:
        scoped_clients:
            api_client:
                base_uri: '%env(API_URL)%'
```

#### 🔧 Improvements:

```php
// 1. Content-Sanitization für User-Input
use Symfony\Component\Security\Core\Security;

$cleanContent = strip_tags($content, '<p><br><strong><em><u><h1><h2>');

// 2. Validierung längerer Strings
#[Assert\Length(min: 10, max: 50000)]
public string $content;

// 3. Null-Byte-Prävention
$filename = str_replace("\0", '', $filename);
```

### 3. **Database & Query Security**

#### ✅ Stärken:

- **Doctrine ORM:** Automatische SQL-Injection-Protection via Prepared Statements
- **Parameterized Queries**

```php
// ✅ Sichere Query (nicht SQL-Injection anfällig)
$post = $this->em->getRepository(Post::class)->findBy(['category' => $categoryId]);

// ✅ DQL mit Parametern
$this->em->createQuery('DELETE FROM App\Entity\Post p WHERE p.category IN (:ids)')
    ->setParameter('ids', $order)
    ->execute();
```

#### ⚠️ Observations:

```php
// ⚠️ N+1 Query Problem möglich
public function index(EntityManagerInterface $em): JsonResponse
{
    $posts = $em->getRepository(Post::class)->findAll();  // 1 Query
    
    $json = $serializer->serialize($posts, 'json', ['groups' => 'post']);  
    // Lazy-Loading: 1 Query pro Post-Author! (N+1 Problem)
}

// ✅ Empfehlung: JOIN
$qb = $em->getRepository(Post::class)->createQueryBuilder('p');
$posts = $qb
    ->leftJoin('p.author', 'a')
    ->addSelect('a')
    ->leftJoin('p.category', 'c')
    ->addSelect('c')
    ->getQuery()
    ->getResult();
```

#### 🔧 Optimization:

```php
// DQL mit JOINs für Eager Loading
public function getPostsWithRelations(): array
{
    return $this->createQueryBuilder('p')
        ->leftJoin('p.author', 'a')
        ->addSelect('a')
        ->leftJoin('p.category', 'c')
        ->addSelect('c')
        ->leftJoin('p.images', 'img')
        ->addSelect('img')
        ->getQuery()
        ->getResult();
}
```

### 4. **File Upload Security**

#### ✅ Stärken:

- **Datei-Typ-Validierung via guessExtension()**
- **Eindeutige Dateinamen (uniqid() suffix)**
- **Upload-Verzeichnis außerhalb der Web-Root möglich**

```php
// src/Service/PostService.php
$newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;
```

#### ⚠️ Issues:

```php
// ⚠