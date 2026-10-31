# MCP-Endpunkt der BlogAPI

Die BlogAPI stellt einen **MCP-Server** (Model Context Protocol) bereit, über den
KI-Clients wie Vibe/Mistral, Claude oder Cursor authentifiziert Blogbeiträge
inklusive Beitragsbildern erstellen und verwalten können.

- **Transport:** Streamable HTTP (MCP-Spezifikation 2025-06-18; abwärtskompatibel zu 2025-03-26 und 2024-11-05)
- **Endpunkt:** `POST /api/mcp`
- **Authentifizierung:** `Authorization: Bearer mcp_<token>` (dedizierte, langlebige MCP-Tokens)
- **Zustandslosigkeit:** Keine Server-Sessions; jede Anfrage wird eigenständig authentifiziert

---

## 1. Architektur und Sicherheitsmodell

```
KI-Client (z.B. Vibe/Mistral)
        |
        | POST /api/mcp  (JSON-RPC 2.0)
        | Authorization: Bearer mcp_<token>
        v
+--------------------------------------------------+
| Symfony Firewall (access_control)                 |
|  - McpTokenAuthenticator: prüft Bearer-Token     |
|    (SHA-256-Hash-Lookup in mcp_token)            |
+--------------------------------------------------+
        |
        v
+--------------------------------------------------+
| McpController -> McpServer (JSON-RPC)            |
|  - initialize / ping / tools/list / tools/call   |
+--------------------------------------------------+
        |
        v
+--------------------------------------------------+
| McpToolProvider (8 Tools)                         |
|  -> PostService / CategoryService (Businesslogik) |
+--------------------------------------------------+
```

Sicherheitseigenschaften:

1. **Kein Klartext-Token in der Datenbank.** Gespeichert wird ausschließlich ein SHA-256-Hash (`mcp_token.token_hash`). Der Klartext-Token wird nur einmalig bei der Erstellung ausgegeben.
2. **Widerrufbarkeit.** `php bin/console app:mcp:revoke-token` entzieht einen Token sofort; jede laufende Anfrage danach wird mit HTTP 401 abgewiesen.
3. **Benutzerbindung.** Jeder Token gehört zu genau einem Benutzer. Autor-Prüfungen (Bearbeiten/Löschen nur durch den Autor) gelten unverändert.
4. **Vollständige Absicherung.** Auch der `initialize`-Handshake erfordert Authentifizierung (`access_control: ^/api/mcp$ -> IS_AUTHENTICATED_FULLY`).
5. **Auditierbarkeit.** `mcp_token.last_used_at` protokolliert die letzte Nutzung.
6. **Gleiche Validierung wie REST.** Bilder durchlaufen dieselben Prüfungen wie reguläre Uploads (MIME-Whitelist JPEG/PNG/GIF/WebP, 5MB-Limit, finfo-Inhaltsprüfung, sicherer Dateiname). Beitragsinhalte werden durch HTMLPurifier sanitisiert.
7. **WWW-Authenticate-Header.** Ungültige Requests erhalten HTTP 401 mit `WWW-Authenticate: Bearer`, sodass Clients (z.B. Vibe) die Authentifizierungsmethode automatisch erkennen.

Abgrenzung zum bestehenden JWT-Cookie-Authenticator: Browser/Frontend authentifizieren sich weiterhin über das HttpOnly-`BEARER`-Cookie. Der `Authorization`-Header ist bewusst dem MCP-Pfad zugeordnet, da MCP-Clients keine Cookies verwalten und statische, langlebige Bearer-Credentials benötigen (JWT-TTL: 3600 Sekunden).

---

## 2. MCP-Token verwalten

### Token erstellen

```bash
php bin/console app:mcp:create-token info@jenssmit.de --name=Vibe
```

Ausgabe (Token nur einmal sichtbar):

```
MCP-Token erstellt.

  Token (nur jetzt sichtbar, bitte sicher speichern):
  mcp_3f9a1c8e5b2d7f4a6c9e1b8d3f5a7c2e4b6d9f1a3c5e7b9d

  Verwendung im Authorization-Header:
  Authorization: Bearer mcp_3f9a1c8e5b2d7f4a6c9e1b8d3f5a7c2e4b6d9f1a3c5e7b9d
```

### Token widerrufen

```bash
# per Klartext-Token oder interner ID
php bin/console app:mcp:revoke-token mcp_3f9a1c8e5b2d...
```

### Hinweise zum Datenbankschema

Die Tabelle `mcp_token` wird über die Doctrine-Migration `Version20261031120000` erstellt:

```bash
php bin/console doctrine:migrations:migrate
```

---

## 3. JSON-RPC-Methoden

| Methode | Beschreibung |
|---------|--------------|
| `initialize` | Handshake; handelt die Protokollversion aus |
| `notifications/initialized` | Abschluss des Handshakes (Notification, keine Antwort) |
| `ping` | Lebenszeichen (`result: {}`) |
| `tools/list` | Liefert alle Tool-Definitionen inkl. JSON-Schema |
| `tools/call` | Führt ein Tool aus (`params: {name, arguments}`) |

Nicht unterstützte Methoden liefern den JSON-RPC-Fehler `-32601`; ungültige
Argumente `-32602`; ungültiges JSON `-32700`.

### Handshake-Beispiel

```bash
curl -X POST https://<api-host>/api/mcp \
  -H "Authorization: Bearer mcp_<token>" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2025-06-18",
      "capabilities": {},
      "clientInfo": {"name": "TestClient", "version": "1.0.0"}
    }
  }'
```

Antwort:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2025-06-18",
    "capabilities": {"tools": {"listChanged": false}},
    "serverInfo": {"name": "BlogAPI MCP", "version": "1.0.0"},
    "instructions": "BlogAPI MCP-Server: ..."
  }
}
```

`GET /api/mcp` und `DELETE /api/mcp` antworten mit HTTP 405, da der Server
weder einen SSE-Stream noch Sessions anbietet.

---

## 4. Tool-Referenz

### list_categories (Lesen)
Listet alle Kategorien mit IDs. Liefert die `categoryId` für `create_blog_post`.

### create_category (Schreiben)
Erstellt eine Kategorie. Argumente: `name` (Pflicht, 2-255 Zeichen), `parentId` (optional).

### upload_image (Schreiben)
Lädt ein Bild hoch und gibt `filename` und `url` zurück. Genau eines von:
- `base64Data`: Bildinhalt Base64-kodiert
- `imageUrl`: direkt herunterladbare http(s)-URL

Optional: `filename` (Basis-Dateiname). Erlaubte Typen: JPEG, PNG, GIF, WebP; maximal 5 MB. Der Rückgabe-`filename` ist die Referenz für `create_blog_post`.

### create_blog_post (Schreiben)
Erstellt einen Beitrag. Argumente:
- `title` (Pflicht)
- `content` (Pflicht, HTML; erlaubte Tags: p, br, strong, em, u, a[href], img[src|alt|style], h2, h3, ul, ol, li)
- `categoryId` (Pflicht, siehe `list_categories`)
- `titleImageFilename` (Pflicht, Rückgabewert von `upload_image`)
- `additionalImageFilenames` (optional, Array bereits hochgeladener Bilder)

### list_posts (Lesen)
Neueste Beiträge mit ID, Titel, Slug, Kategorie, Datum. Argument: `limit` (optional, max. 50).

### get_post (Lesen)
Einzelner Beitrag per ID oder Slug, inklusive Inhalt. Argument: `identifier`.

### update_blog_post (Schreiben)
Aktualisiert Titel, Inhalt und/oder Kategorie eines eigenen Beitrags. Argumente: `id` (Pflicht), `title`, `content`, `categoryId` (alle optional).

### delete_blog_post (Schreiben)
Löscht einen eigenen Beitrag inklusive aller zugehörigen Bilddateien. Argument: `id`.

### Typischer Workflow

```
1. tools/call list_categories          -> categoryId ermitteln
2. tools/call upload_image            -> filename + url des Beitragsbilds
3. tools/call create_blog_post        -> Beitrag mit Titel, HTML, Kategorie, Bild
4. tools/call get_post                -> Ergebnis prüfen
```

### Beispiel: Beitrag mit Beitragsbild erstellen

```bash
curl -X POST https://<api-host>/api/mcp \
  -H "Authorization: Bearer mcp_<token>" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "create_blog_post",
      "arguments": {
        "title": "Symfony 7 und MCP",
        "content": "<p>In diesem Beitrag ...</p>",
        "categoryId": 1,
        "titleImageFilename": "titelbild-3f9a1c8e5b2d7f4a.png"
      }
    }
  }'
```

---

## 5. Anbindung in Vibe / Mistral

1. In Vibe (chat.mistral.ai) den Bereich **Context > Connectors** öffnen.
2. **Add Connector** wählen und zum Tab **Custom MCP Connector** wechseln.
3. Felder ausfüllen:
   - **Connector name:** z.B. `BlogAPI` (keine Leerzeichen/Sonderzeichen)
   - **Server URL:** `https://<api-host>/api/mcp` (HTTPS mit gültigem Zertifikat erforderlich)
   - **Description:** optional
4. **Connect** klicken. Die Plattform erkennt die Bearer-Token-Authentifizierung automatisch (HTTP 401 mit `WWW-Authenticate: Bearer`).
5. Den MCP-Token (`mcp_...`) als Bearer-Token hinterlegen und die Authentifizierung abschließen.
6. Im Chat z.B. verwenden: "Erstelle einen Blogbeitrag über X mit Kategorie Y und lade vorher ein Beitragsbild hoch."

Im Anschluss empfiehlt es sich, unter **My Connectors > Functions** die Schreib-Funktionen (`create_blog_post`, `upload_image`, ...) zunächst auf manuelle Freigabe zu stellen und nur `list_categories`, `list_posts` und `get_post` vorab zu autorisieren.

---

## 6. Tests

Funktionale Tests: `tests/Controller/McpControllerTest.php`

Abgedeckte Szenarien:

- Authentifizierung: fehlender, ungültiger, präfixloser und widerrufener Token (HTTP 401)
- Handshake: `initialize` (inkl. Versionsverhandlung), `ping`, Notification (HTTP 202)
- Fehlerfälle: ungültiges JSON (-32700), unbekannte Methode (-32601), unbekanntes Tool (-32602), fehlende Pflichtargumente
- Vollständiger Workflow: `create_category` -> `upload_image` -> `create_blog_post` -> `list_posts` -> `get_post`
- Sicherheit: XSS-Sanitization des Inhalts, Autor-Prüfung bei `delete_blog_post`

Ausführung:

```bash
vendor/bin/phpunit --filter McpControllerTest
vendor/bin/phpunit --testdox   # gesamte Suite (läuft auch in GitHub Actions, Job "php-tests")
```

---

## 7. Aufnahme in das Portfolio (jenssmit.de)

Die Implementierung wurde bewusst in der BlogAPI entwickelt. Für die spätere
Übernahme in das Portfolio-Backend (JensSmit_v_1.0) sind folgende Bestandteile
zu übernehmen:

| Bestandteil | Pfad |
|-------------|------|
| Entität | `src/Entity/McpToken.php` |
| Repository | `src/Repository/McpTokenRepository.php` |
| Authenticator | `src/Security/McpTokenAuthenticator.php` |
| Konsolenbefehle | `src/Command/CreateMcpTokenCommand.php`, `src/Command/RevokeMcpTokenCommand.php` |
| MCP-Kern | `src/Mcp/McpServer.php`, `src/Mcp/McpToolProvider.php` |
| Controller | `src/Controller/McpController.php` |
| PostService-Erweiterung | `storeImageData()`, `createPostFromStoredImages()` in `src/Service/PostService.php` |
| Security-Konfiguration | Firewall-Eintrag `McpTokenAuthenticator` und `access_control`-Regel `^/api/mcp$` in `config/packages/security.yaml` |
| Service-Binding | `App\Mcp\McpToolProvider` mit `%env(APP_URL)%` in `config/services.yaml` |
| Migration | `migrations/Version20261031120000.php` |
| Tests | `tests/Controller/McpControllerTest.php` |
