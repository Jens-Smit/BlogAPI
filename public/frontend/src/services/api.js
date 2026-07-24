import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Der alte Request-Interceptor wird gelöscht, da der Browser
// die Autorisierung via Cookie nun selbst übernimmt.

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Refresh-Logik
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      try {
        // Das Backend prüft nun auch hier den HttpOnly Refresh-Cookie
        await axios.post('/api/token/refresh', {}, {
          withCredentials: true,
          headers: { 'Content-Type': 'application/json' },
        });

        // Wenn der Refresh klappt, Request einfach nochmal absenden
        return api(originalRequest);
      } catch (refreshError) {
        localStorage.removeItem('is_logged_in');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }
    return Promise.reject(error);
  }
);

export default api;
