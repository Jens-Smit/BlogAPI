/**
 * Zentrale Konfigurationsdatei für die Frontend-Anwendung
 * Verwaltet API-URLs, Endpunkte und andere Umgebungsspezifische Einstellungen
 */

const config = {
  /**
   * API Base URL - wird aus Umgebungsvariablen geladen
   * Fallback auf localhost für Development
   */
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',

   /**
    * Generiert vollständige URLs für Uploads
    * @param {string} filename - Der Dateiname des Uploads
    * @returns {string} Die vollständige URL
    */
   getUploadUrl: (filename) => {
     if (!filename) return '';
     // Use the correct API path that matches the backend
     return `${config.apiBaseUrl}/uploads/${filename}`;
   },

  /**
   * Generiert vollständige URLs für API-Endpunkte
   * @param {string} endpoint - Der API-Endpunkt (ohne führenden Slash)
   * @returns {string} Die vollständige API-URL
   */
  getApiUrl: (endpoint) => {
    if (!endpoint) return config.apiBaseUrl;
    // Entferne führende Slashes für Konsistenz
    const cleanEndpoint = endpoint.replace(/^\/+/, '');
    return `${config.apiBaseUrl}/api/${cleanEndpoint}`;
  },

  /**
   * API Endpunkte - zentrale Definition aller API-Pfade
   */
  api: {
    base: '/api',
    posts: '/posts',
    auth: {
      login: '/login',
      refresh: '/token/refresh',
      logout: '/logout'
    },
    uploads: '/uploads'
  }
};

export default config;