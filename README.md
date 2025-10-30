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
- [Testing](#-testing)
- [Deployment](#-deployment)


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
git clone https://github.com/Jens-Smit/BlogAPI.git
cd BlogAPI
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

- **Issues:** [GitHub Issues](https://github.com/Jens-Smit/BlogAPI/issues)
- **Email:** info@jenssmit.com
- **Documentation:** [Full Docs](https://jenssmit.de/api/doc)

---

**Zuletzt aktualisiert:** 2024-01-15  
**Entwickler:**  [Jens Smit](https://jenssmit.de) 
