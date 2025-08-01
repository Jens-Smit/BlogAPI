# Symfony Blog API

Eine vollständige Blog-API mit Benutzerauthentifizierung, CAPTCHA-System und umfassender Dateiverwaltung, entwickelt mit Symfony 6.1 und PHP 8.1+.

## 📋 Inhaltsverzeichnis

- [Features](#-features)
- [Technologien](#-technologien)
- [Installation](#-installation)
- [Konfiguration](#-konfiguration)
- [API-Dokumentation](#-api-dokumentation)
- [Authentifizierung](#-authentifizierung)
- [Entwicklung](#-entwicklung)
- [Tests](#-tests)
- [Deployment](#-deployment)
- [API-Endpoints](#-api-endpoints)

## 🚀 Features

### Benutzer-Management
- ✅ Benutzerregistrierung mit E-Mail und Passwort
- ✅ JWT-basierte Authentifizierung
- ✅ Sichere Passwort-Hashing
- ✅ Login/Logout-Funktionalität

### Blog-System
- ✅ CRUD-Operationen für Blog-Posts
- ✅ Mehrfache Bild-Uploads pro Post
- ✅ Titel-Bild für Posts
- ✅ Autor-Zuordnung und Berechtigungsmanagement
- ✅ Zeitstempel für Erstellungsdatum

### CAPTCHA-System
- ✅ Dynamisches, rotierbares CAPTCHA
- ✅ Verschiedene Formen und Farben
- ✅ Interaktive Rotation durch Benutzerklicks
- ✅ Session-basierte Validierung

### Weitere Features
- ✅ Kontaktformular mit E-Mail-Versand
- ✅ Umfassende API-Dokumentation mit Swagger/OpenAPI
- ✅ CORS-Unterstützung
- ✅ Vollständige Testabdeckung
- ✅ File-Upload-Management

## 🛠 Technologien

### Backend
- **PHP 8.1+**
- **Symfony 6.1**
- **Doctrine ORM** - Datenbankabstraktion
- **Lexik JWT Authentication Bundle** - JWT-Token-Management
- **Nelmio API Doc Bundle** - Swagger/OpenAPI-Dokumentation
- **Nelmio CORS Bundle** - Cross-Origin-Resource-Sharing

### Datenbank
- **MySQL/MariaDB** (konfigurierbar für andere Datenbanken)

### Testing
- **PHPUnit** - Unit- und Integrationstests
- **Symfony Test Framework** - Controller-Tests

## 🚀 Installation

### Voraussetzungen
- PHP 8.1 oder höher
- Composer
- MySQL/MariaDB
- Node.js (optional, für Frontend-Development)

### Schritt-für-Schritt Installation

1. **Repository klonen**
```bash
git clone <repository-url>
cd symfony-blog-api
```

2. **Dependencies installieren**
```bash
composer install
```

3. **Umgebungsvariablen konfigurieren**
```bash
cp .env .env.local
```

4. **Datenbank erstellen**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **JWT-Schlüssel generieren**
```bash
php bin/console lexik:jwt:generate-keypair
```

6. **Uploads-Ordner erstellen**
```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

7. **Development-Server starten**
```bash
symfony server:start
# oder
php -S localhost:8000 -t public/
```

## ⚙️ Konfiguration

### Umgebungsvariablen (.env.local)

```bash
# Datenbank-Konfiguration
DATABASE_URL="mysql://username:password@127.0.0.1:3306/blog_db?serverVersion=8.0"

# Mailer-Konfiguration
MAILER_DSN=smtp://localhost:1025

# JWT-Konfiguration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# App-Umgebung
APP_ENV=dev
APP_SECRET=your_app_secret
```

### Uploads-Konfiguration

Die Upload-Pfade sind in `config/services.yaml` konfiguriert:

```yaml
parameters:
    upload_directory: '%kernel.project_dir%/public/uploads'
```

## 📚 API-Dokumentation

Die vollständige API-Dokumentation ist über Swagger UI verfügbar:

**URL:** `http://localhost:8000/api/doc`

### Authentifizierung

Die API verwendet JWT-Token für die Authentifizierung. Nach dem Login erhalten Sie einen Token, der in nachfolgenden Requests im Authorization-Header mitgesendet werden muss:

```
Authorization: Bearer <your-jwt-token>
```

## 🔐 Authentifizierung

### Registrierung
```bash
POST /register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

### Login
```bash
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Antwort:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

## 🧪 Tests

### Alle Tests ausführen
```bash
php bin/phpunit
```

### Spezifische Testsuites
```bash
# Controller-Tests
php bin/phpunit tests/Controller/

# Service-Tests
php bin/phpunit tests/Service/

# Mit Coverage-Report
php bin/phpunit --coverage-html coverage/
```

### Test-Kategorien

- **Unit Tests:** Service-Logik und Business-Rules
- **Integration Tests:** Controller-Endpoints und Datenbankinteraktionen
- **Functional Tests:** End-to-End-Szenarien

## 🔧 Entwicklung

### Code-Standards
```bash
# PHP-Code-Style-Fixes
php bin/console php-cs-fixer fix

# Static Analysis
vendor/bin/phpstan analyse src tests
```

### Datenbank-Migration erstellen
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Neue Entity erstellen
```bash
php bin/console make:entity
```

## 🚀 Deployment

### Production-Setup

1. **Environment konfigurieren**
```bash
APP_ENV=prod
APP_DEBUG=false
```

2. **Assets optimieren**
```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
```

3. **Datenbank migrieren**
```bash
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

4. **Webserver konfigurieren** (Apache/Nginx)

### Sicherheitshinweise

- JWT-Schlüssel sicher aufbewahren
- HTTPS in Production verwenden
- Upload-Ordner-Berechtigungen prüfen
- Datenbank-Credentials sichern

## 📋 API-Endpoints

### Authentifizierung
| Method | Endpoint | Beschreibung | Auth |
|--------|----------|--------------|------|
| POST | `/register` | Benutzerregistrierung | ❌ |
| POST | `/login` | Benutzeranmeldung | ❌ |

### Blog-Posts
| Method | Endpoint | Beschreibung | Auth |
|--------|----------|--------------|------|
| GET | `/posts` | Alle Posts abrufen | ❌ |
| POST | `/posts` | Neuen Post erstellen | ✅ |
| POST | `/posts/{id}` | Post aktualisieren | ✅ |
| DELETE | `/posts/{id}` | Post löschen | ✅ |

### File-Uploads
| Method | Endpoint | Beschreibung | Auth |
|--------|----------|--------------|------|
| POST | `/posts/upload` | Mediendatei hochladen | ✅ |

### CAPTCHA
| Method | Endpoint | Beschreibung | Auth |
|--------|----------|--------------|------|
| GET | `/api/captcha/generate` | CAPTCHA generieren | ❌ |
| POST | `/api/captcha/verify` | CAPTCHA verifizieren | ❌ |

### Kontakt
| Method | Endpoint | Beschreibung | Auth |
|--------|----------|--------------|------|
| POST | `/api/contact` | Kontaktnachricht senden | ❌ |

## 📁 Projektstruktur

```
src/
├── Controller/          # API-Controller
│   ├── AuthController.php
│   ├── PostController.php
│   ├── CaptchaController.php
│   └── ContactController.php
├── Entity/              # Doctrine-Entities
│   ├── User.php
│   └── Post.php
├── DTO/                 # Data Transfer Objects
│   ├── RegisterRequestDTO.php
│   ├── PostCreateDTO.php
│   └── PostUpdateDTO.php
├── Service/             # Business-Logic-Services
│   ├── AuthService.php
│   ├── PostService.php
│   └── CaptchaGeneratorService.php
├── Repository/          # Doctrine-Repositories
└── Security/           # Security-Konfiguration

tests/
├── Controller/         # Controller-Tests
├── Service/           # Service-Tests
└── bootstrap.php      # Test-Bootstrap

config/
├── packages/          # Bundle-Konfiguration
├── routes/           # Routing-Konfiguration
└── services.yaml     # Service-Container

public/
├── uploads/          # Upload-Ordner
└── index.php        # Entry-Point
```

## 🔍 CAPTCHA-System

Das CAPTCHA-System generiert interaktive, rotierbare Bildpuzzles:

### Features
- **4 Bildteile** die einzeln rotiert werden können
- **Verschiedene Formen:** Kreise und Quadrate
- **Zufällige Farben** und Skalierungen
- **45°-Rotation** pro Klick
- **Session-basierte Validierung**

### Verwendung
```javascript
// CAPTCHA generieren
fetch('/api/captcha/generate')
  .then(response => response.json())
  .then(data => {
    // data.imageParts - Array von Base64-Bildern
    // data.initialRotations - Startrotationen
    // data.captchaId - Session-ID
  });

// CAPTCHA verifizieren
fetch('/api/captcha/verify', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    captchaId: 'captcha_id',
    userClicks: [2, 4, 0, 6] // Anzahl Klicks pro Teil
  })
});
```

## 🤝 Mitwirken

1. Fork des Repositories erstellen
2. Feature-Branch erstellen (`git checkout -b feature/amazing-feature`)
3. Änderungen committen (`git commit -m 'Add amazing feature'`)
4. Branch pushen (`git push origin feature/amazing-feature`)
5. Pull Request erstellen

## 📝 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe `LICENSE`-Datei für Details.

## 🐛 Bug-Reports & Support

Bei Problemen oder Fragen:

1. Prüfen Sie die [Issues](link-to-issues)
2. Erstellen Sie ein neues Issue mit detaillierter Beschreibung
3. Nutzen Sie die API-Dokumentation unter `/api/doc`

## 📊 Monitoring & Logging

### Development
- Symfony Profiler: `/_profiler`
- Debug-Toolbar aktiviert
- Detaillierte Fehlermeldungen

### Production
- Monolog für strukturiertes Logging
- Fehler werden in `var/log/` gespeichert
- Performance-Monitoring verfügbar

---

**Entwickelt mit ❤️ und Symfony**