# GastroOS Hilfe-Center

Statische Hilfeseite (Anleitungen, FAQ, Support-Kontakt) für **GastroOS** von Vision Gastro – gehostet unter [hilfe.visiongastro.de](https://hilfe.visiongastro.de).

Im GastroOS-Farbkonzept umgesetzt: dunkles Theme (`#0f0e0c`/`#1a1815`) mit warmem Amber-Akzent (`#e8a33d`) und Kupfer-Secondary (`#c96f2d`).

## Projektstruktur (Clean-Code-Prinzipien)

```
help-center/
├── src/                     # Statische Seite – der gesamte Inhalt
│   ├── index.html           # Nur Struktur/Semantik, kein Inhalt hardcoded
│   ├── css/main.css         # Design-Tokens als CSS Custom Properties (Design zentral)
│   ├── js/app.js            # Rendering-Logik (kleine, reine Funktionen)
│   └── data/content.js      # ALLE Inhalte (Kategorien, Anleitungen, FAQ, Kontakt) ausgelagert
├── deploy/
│   ├── nginx.conf           # Produktiv-nginx-Konfiguration (Caching, Security-Header, Gzip)
│   └── docker-compose.prod.yml  # Läuft auf demselben Server wie die Referenzprojekte
├── Dockerfile               # nginx:1.27-alpine + Healthcheck
├── docker-compose.yml       # Lokale Entwicklung (Port 127.0.0.1:8082)
└── README.md
```

**Prinzipien:** Trennung von Inhalt (`data/`), Struktur (`index.html`), Stil (`css/`) und Verhalten (`js/`); zentrale Design-Tokens; keine Framework-Abhängigkeit; barrierearme, semantische Elemente.

## Inhalte pflegen

Alle Texte, FAQ-Einträge und Kontaktdaten liegen zentral in `src/data/content.js` (`window.GASTRO_OS_HELP`). Ohne Code-Änderung anpassbar; Suche, Kategorien und FAQ aktualisieren sich automatisch.

## Lokal starten

```bash
cd help-center
docker compose up --build
# → http://localhost:8082
```

## Benötigte GitHub Secrets

Unter **Settings → Secrets and variables → Actions** (Repository `Jens-Smit/BlogAPI`) konfigurieren:

| Secret | Beschreibung | Beispiel |
|---|---|---|
| `DEPLOY_HOST` | IP/Hostname des Servers, auf dem auch die Referenzprojekte laufen | `203.0.113.10` |
| `DEPLOY_PORT` | SSH-Port des Servers | `22` |
| `DEPLOY_USER` | SSH-Benutzer mit Docker-Rechten auf dem Server | `deploy` |
| `DEPLOY_SSH_KEY` | **Privater** SSH-Schlüssel (OpenSSH-Format, komplett inkl. `-----BEGIN ... KEY-----` / `-----END ... KEY-----`) | – |
| `DEPLOY_PATH` | Absoluter Pfad auf dem Server, der `deploy/docker-compose.prod.yml` aus diesem Repo enthält (once geklont) | `/opt/gastroos-help-center` |
| `GHCR_PULL_USER` | GitHub-Benutzername für `docker login ghcr.io` auf dem Server | `Jens-Smit` |
| `GHCR_PULL_TOKEN` | GitHub PAT mit `read:packages` für den Pull des Images | `ghp_…` |

Hinweise:
- `GITHUB_TOKEN` (automatisch vorhanden) reicht für den Image-Push zu GHCR im Build-Job.
- `GHCR_PULL_TOKEN` ist nötig, weil der Server sich beim `docker compose pull` separat bei GHCR authentifizieren muss (das Workflow-Token existiert auf dem Server nicht).
- Der Secret-Scan und die Berechtigungen im Workflow sind auf `packages: write` + `contents: read` minimiert.

## Server-Einrichtung (einmalig)

```bash
# Auf dem Server:
git clone https://github.com/Jens-Smit/BlogAPI.git /opt/gastroos-help-center
cd /opt/gastroos-help-center
docker compose -f help-center/deploy/docker-compose.prod.yml up -d
```

Voraussetzung: Der Container bindet nur `127.0.0.1:8082:80`. Die externe Erreichbarkeit unter `hilfe.visiongastro.de` übernimmt der Reverse-Proxy des Servers (z. B. nginx/Traefik, wie bei den Referenzprojekten) mit TLS.

## CI/CD (GitHub Actions)

Workflow: [`.github/workflows/help-center.yml`](../.github/workflows/help-center.yml)

1. Bei jedem Push auf `master` (Änderungen unter `help-center/`): Build des Docker-Images.
2. Push nach `ghcr.io/jens-smit/blogapi/help-center:latest` + versioniertes Tag.
3. Deploy per SSH auf den Server: `docker compose pull && up -d` (Umgebung `production`).

Pull Requests bauen das Image nur (kein Push, kein Deploy). `workflow_dispatch` erlaubt manuelle Auslösung.
