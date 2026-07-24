import api from './api';

const isAuthenticated = () => {
  if (typeof window === 'undefined') return false;
  // Da die Cookies jetzt HttpOnly sind, prüfen wir ein Flag
  return localStorage.getItem('is_logged_in') === 'true';
};

// Get current user data
const getCurrentUser = async () => {
  try {
    const response = await api.get('/me');
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Benutzerdaten:', error);
    throw error;
  }
};

// Login user
const login = async (email, password) => {
  try {
    const response = await api.post('/login', { email, password });
    if (response.status === 200) {
      // Das Backend hat nun die HttpOnly-Cookies gesetzt.
      // Wir setzen nur ein Flag für das Frontend.
      localStorage.setItem('is_logged_in', 'true');
      return response.data;
    }
  } catch (error) {
    console.error('Login-Fehler:', error);
    throw new Error('Ungültige Anmeldedaten');
  }
};

// Logout user
const logout = async () => {
  try {
    await api.post('/logout'); // Das Backend löscht die Cookies
  } catch (error) {
    console.error('Logout-Fehler:', error);
  } finally {
    localStorage.removeItem('is_logged_in');
    window.location.href = '/login';
  }
};

// Register user
const register = async (username, email, password) => {
  try {
    const response = await api.post('/register', {
      username,
      email,
      password,
    });
    return response.data;
  } catch (error) {
    console.error('Registrierungsfehler:', error);
    throw error;
  }
};

export { isAuthenticated, getCurrentUser, login, logout, register };
