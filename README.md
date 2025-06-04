# BlogAPI

Eine RESTful API für ein einfaches Blog-System, entwickelt mit Symfony 6 und abgesichert via JSON Web Tokens (JWT). Über OpenAPI-Annotationen sind sämtliche Endpunkte dokumentiert; die generierte Spezifikation kann interaktiv über Swagger UI im Browser eingesehen werden.

---

## Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Anforderungen](#anforderungen)
3. [Installation & Einrichtung](#installation--einrichtung)

   * [Repository klonen](#repository-klonen)
   * [Umgebungsvariablen konfigurieren](#umgebungsvariablen-konfigurieren)
   * [Abhängigkeiten installieren](#abhängigkeiten-installieren)
   * [JWT-Schlüssel generieren](#jwt-schlüssel-generieren)
   * [Datenbank konfigurieren und Migrationen ausführen](#datenbank-konfigurieren-und-migrationen-ausführen)
4. [Projekt starten](#projekt-starten)
5. [Authentifizierung (JWT)](#authentifizierung-jwt)
6. [API-Endpunkte](#api-endpunkte)

   * [Users](#users)
   * [Posts](#posts)
7. [Swagger UI / OpenAPI-UI](#swagger-ui--openapi-ui)
8. [Tests](#tests)
9. [Fehlerbehandlung & Logging](#fehlerbehandlung--logging)
10. [Wartung & Weiterentwicklung](#wartung--weiterentwicklung)
11. [Entity-Übersicht](#entity-übersicht)
12. [Lizenz](#lizenz)

---

## Überblick

Dieses Projekt stellt eine einfache Blog-API bereit, in der sich Benutzer registrieren, authentifizieren und CRUD-Operationen (Create/Read/Update/Delete) an Blog-Posts durchführen können. Die API ist geschützt mittels JWT-basierter Authentifizierung und nutzt Symfony 6. Alle Endpunkte sind dokumentiert via OpenAPI (Swagger).

---

## Anforderungen

* PHP 8.1 oder höher
* [Composer](https://getcomposer.org/)
* Symfony CLI (optional)
* MySQL- oder PostgreSQL-Server (oder SQLite für Tests)
* OpenSSL (um JWT-Schlüssel zu generieren)
* Git

---

## Installation & Einrichtung

### Repository klonen

```bash
git clone https://github.com/Jens-Smit/BlogAPI.git
cd BlogAPI
```

### Umgebungsvariablen konfigurieren

Kopiere die Beispieldatei und passe an:

```bash
cp .env .env.local
```

Öffne `.env.local` und ändere:

```dotenv
APP_ENV=dev
APP_SECRET=<dein_app_secret>

# Datenbank (MySQL-Beispiel)
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/blogapi?serverVersion=5.7"

# JWT-Konfiguration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pub
JWT_PASSPHRASE="dein_jwt_passphrase"
```

### Abhängigkeiten installieren

```bash
composer install
```

### JWT-Schlüssel generieren

```bash
mkdir -p config/jwt
# Private Key (RSA 4096, mit Passphrase)
openssl genrsa -out config/jwt/private.pem -aes256 4096
# Public Key
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pub
```

> **Hinweis:** Die Passphrase muss mit `JWT_PASSPHRASE` übereinstimmen.

### Datenbank konfigurieren und Migrationen ausführen

1. **Datenbank anlegen:**

   ```bash
   php bin/console doctrine:database:create
   ```
2. **Migrationen erstellen und ausführen:**

   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

---

## Projekt starten

1. **Symfony Built-in Webserver:**

   ```bash
   symfony server:start
   ```

   Zugriff unter `https://127.0.0.1:8000`.

2. **PHP Built-in-Webserver:**

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

   Dann `http://127.0.0.1:8000` verwenden.

> Achte darauf, dass `APP_ENV=dev` gesetzt ist, damit Dev-Tools verfügbar sind.

---

## Authentifizierung (JWT)

### 1. Registrierung

* **Endpunkt:** `POST /api/register`
* **Beschreibung:** Legt einen neuen Benutzer an.
* **Request (JSON):**

  ```json
  {
    "email": "max@beispiel.de",
    "username": "maxmustermann",
    "password": "geheimesPasswort"
  }
  ```
* **Response (201 Created):**

  ```json
  {
    "id": 1,
    "email": "max@beispiel.de",
    "username": "maxmustermann",
    "roles": ["ROLE_USER"],
    "createdAt": "2025-06-04T12:34:56+02:00"
  }
  ```
* **Fehler:**

  * `400 Bad Request` (fehlende/ungültige Daten)
  * `409 Conflict` (E-Mail/Username existiert bereits)

### 2. Login / Token-Abruf

* **Endpunkt:** `POST /api/login_check`
* **Beschreibung:** Authentifiziert und liefert JWT.
* **Request (JSON):**

  ```json
  {
    "username": "maxmustermann",
    "password": "geheimesPasswort"
  }
  ```
* **Response (200 OK):**

  ```json
  {
    "token": "<jwt_token>",
    "refresh_token": "<refresh_token>"  
  }
  ```
* **Fehler:**

  * `401 Unauthorized` (ungültige Anmeldedaten)

> **Wichtig:** Alle geschützten Endpunkte (z.B. `/api/posts`) benötigen folgenden Header:
>
> ```
> Authorization: Bearer <dein_jwt_token>
> ```

---

## API-Endpunkte

### Users

#### Registrierung

**POST** `/api/register`

* Siehe Abschnitt „Registrierung“ oben.

#### Login / JWT-Abruf

**POST** `/api/login_check`

* Siehe Abschnitt „Login / Token-Abruf“ oben.

### Posts

Ein „Post“ umfasst: `id`, `title`, `content`, `author`, `createdAt`, `updatedAt`.

#### 1. Alle Posts abrufen

**GET** `/api/posts`

* **Security:** Bearer JWT required.
* **Optionale Query-Parameter:** `page` (int), `limit` (int) zur Paginierung.
* **Response (200):**

  ```json
  {
    "data": [
      {
        "id": 3,
        "title": "Erster Blogpost",
        "content": "Hallo Welt! Dies ist mein erster Post.",
        "author": { "id": 1, "username": "maxmustermann" },
        "createdAt": "2025-06-04T13:00:00+02:00",
        "updatedAt": "2025-06-04T14:20:00+02:00"
      },
      ...
    ],
    "meta": {
      "totalItems": 42,
      "itemCount": 10,
      "itemsPerPage": 10,
      "totalPages": 5,
      "currentPage": 1
    }
  }
  ```
* **Fehler:** `401 Unauthorized`

#### 2. Einzelnen Post abrufen

**GET** `/api/posts/{id}`

* **Security:** Bearer JWT.
* **Path-Parameter:** `id` (int)
* **Response (200):**

  ```json
  {
    "id": 3,
    "title": "Erster Blogpost",
    "content": "Hallo Welt! Dies ist mein erster Post.",
    "author": { "id": 1, "username": "maxmustermann" },
    "createdAt": "2025-06-04T13:00:00+02:00",
    "updatedAt": "2025-06-04T14:20:00+02:00"
  }
  ```
* **Fehler:**

  * `401 Unauthorized`
  * `404 Not Found`

#### 3. Neuen Post erstellen

**POST** `/api/posts`

* **Security:** Bearer JWT.
* **Request (JSON):**

  ```json
  {
    "title": "Neuer Blogpost",
    "content": "Das ist der Inhalt meines neuen Posts."
  }
  ```
* **Response (201 Created):**

  ```json
  {
    "id": 4,
    "title": "Neuer Blogpost",
    "content": "Das ist der Inhalt meines neuen Posts.",
    "author": { "id": 2, "username": "annaadmin" },
    "createdAt": "2025-06-04T15:30:00+02:00",
    "updatedAt": null
  }
  ```
* **Fehler:**

  * `400 Bad Request` (fehlende Pflichtfelder)
  * `401 Unauthorized`

#### 4. Post aktualisieren

**PUT** `/api/posts/{id}`

* **Security:** Bearer JWT.
* **Path-Parameter:** `id` (int)
* **Request (JSON):**

  ```json
  {
    "title": "Geänderter Titel",
    "content": "Aktualisierter Inhalt."
  }
  ```
* **Response (200 OK):**

  ```json
  {
    "id": 4,
    "title": "Geänderter Titel",
    "content": "Aktualisierter Inhalt.",
    "author": { "id": 2, "username": "annaadmin" },
    "createdAt": "2025-06-04T15:30:00+02:00",
    "updatedAt": "2025-06-04T16:00:00+02:00"
  }
  ```
* **Fehler:**

  * `400 Bad Request`
  * `401 Unauthorized`
  * `403 Forbidden` (wenn nicht Autor oder ROLE\_ADMIN)
  * `404 Not Found`

#### 5. Post löschen

**DELETE** `/api/posts/{id}`

* **Security:** Bearer JWT.
* **Path-Parameter:** `id` (int)
* **Response:** `204 No Content`
* **Fehler:**

  * `401 Unauthorized`
  * `403 Forbidden`
  * `404 Not Found`

---

## Swagger UI / OpenAPI-UI

Dank der OpenAPI-Annotationen steht eine interaktive Dokumentation bereit.

* **Swagger-UI (HTML):** `https://127.0.0.1:8000/api/docs`
* **OpenAPI-Spezifikation (JSON):** `https://127.0.0.1:8000/api/docs.json`

> Beispielkonfiguration (`config/packages/nelmio_api_doc.yaml`):
>
> ```yaml
> nelmio_api_doc:
>   documentation:
>     info:
>       title: "BlogAPI"
>       description: "RESTful API für Blog-Posts"
>       version: "1.0.0"
>   areas:
>     default:
>       path_patterns: [ "^/api" ]
> ```

---

## Tests

Im Verzeichnis `/tests` befinden sich Unit- und Functional-Tests.

* **Test-Ausführung:**

  ```bash
  php bin/phpunit
  ```
* **Tipp (Functional-Test mit JWT):**

  ```php
  $client->request('GET', '/api/posts', [], [], [
      'HTTP_AUTHORIZATION' => 'Bearer ' . \$jwtToken,
  ]);
  ```

---

## Fehlerbehandlung & Logging

* **ValidationErrors:** Bei ungültigen oder fehlenden Feldern wird `400 Bad Request` mit einem `errors`-Objekt zurückgeliefert.
* **NotFoundHttpException:** `404 Not Found` bei nicht existierenden Ressourcen.
* **AccessDeniedException:** `403 Forbidden` bei unzureichender Berechtigung.

**Logging:**

* Dev-Logs: `var/log/dev.log`
* Prod-Logs: `var/log/prod.log`

> Monolog kann erweitert werden, um Errors per E-Mail oder in externe Systeme zu versenden.

---

## Wartung & Weiterentwicklung

1. **Neue Endpunkte dokumentieren:**

   * In den Controllern `use OpenApi\Annotations as OA` importieren.
   * Mit `@OA\Get()`, `@OA\Post()` usw. annotieren.
   * Anschließend `bin/console lint:swagger` oder `bin/console api:doc:generate` ausführen.

2. **Datenbank-Änderungen:**

   ```bash
   php bin/console make:entity
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

3. **Composer- und Symfony-Updates:**

   * Regelmäßig `composer outdated` prüfen.
   * Bei Symfony-Major-Updates Release-Notes beachten ([https://symfony.com/releases](https://symfony.com/releases)).

4. **Deployment (Produktiv):**

   * `.env.local` anpassen (`DATABASE_URL`, `APP_SECRET`, `JWT_*`).
   * Befehle:

     ```bash
     composer install --no-dev --optimize-autoloader
     php bin/console doctrine:migrations:migrate --no-interaction --env=prod
     php bin/console cache:clear --env=prod --no-debug
     php bin/console cache:warmup --env=prod
     ```
   * Webserver so konfigurieren, dass `public/` das Document Root ist.

---

## Entity-Übersicht

### `User` (Entity)

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=App\Repository\UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int \$id = null;

    /** @ORM\Column(type="string", length=180, unique=true) */
    private string \$email;

    /** @ORM\Column(type="json") */
    private array \$roles = [];

    /** @ORM\Column(type="string") */
    private string \$password;

    /** @ORM\Column(type="string", length=50, unique=true) */
    private string \$username;

    /** @ORM\Column(type="datetime_immutable") */
    private \DateTimeImmutable \$createdAt;

    // ... Getter/Setter, UserInterface-Methoden sowie PrePersist-Callback ...
}
```

### `Post` (Entity)

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\Repository\PostRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int \$id = null;

    /** @ORM\Column(type="string", length=255) */
    private string \$title;

    /** @ORM\Column(type="text") */
    private string \$content;

    /** @ORM\ManyToOne(targetEntity=App\Entity\User::class) @ORM\JoinColumn(nullable=false) */
    private User \$author;

    /** @ORM\Column(type="datetime_immutable") */
    private \DateTimeImmutable \$createdAt;

    /** @ORM\Column(type="datetime", nullable=true) */
    private ?\DateTimeInterface \$updatedAt = null;

    // @ORM\PrePersist, @ORM\PreUpdate für automatische Datumsfelder
    // ... Getter/Setter ...
}
```

---

## Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert. Siehe [LICENSE](LICENSE) für Details.
