# Basis-Image: Node.js 18
FROM node:18-alpine

# Arbeitsverzeichnis setzen
WORKDIR /app

# Abhängigkeiten kopieren (direkt aus dem aktuellen Verzeichnis)
COPY package.json package-lock.json ./

# npm install ausführen
RUN npm install

# Projekt kopieren
COPY . .

# Port freigeben
EXPOSE 3000