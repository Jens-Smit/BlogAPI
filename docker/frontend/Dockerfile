# Basis-Image: Node.js 18
FROM node:18-alpine

# Arbeitsverzeichnis setzen
WORKDIR /app

# Abhängigkeiten kopieren
COPY public/frontend/package.json public/frontend/package-lock.json ./

# npm install ausführen
RUN npm install

# Projekt kopieren
COPY public/frontend/ .

# Port freigeben
EXPOSE 3000