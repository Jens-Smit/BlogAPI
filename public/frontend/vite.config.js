import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  // 'root' wurde entfernt. Vite nutzt jetzt standardmäßig den Ordner, in dem die index.html liegt.
  server: {
    port: 3000,
    // proxy für Docker angepasst:
      proxy: {
        '/api': {
          target: 'http://backend:80', // Port explizit angeben für Docker-Netzwerk
          changeOrigin: true,
          // rewrite entfernt, da Backend /api Präfix erwartet
        },
      },
  },
  // Das Build-Objekt wurde vorerst entfernt, da Vite standardmäßig 
  // einen sauberen 'dist'-Ordner im aktuellen Verzeichnis erstellt.
});