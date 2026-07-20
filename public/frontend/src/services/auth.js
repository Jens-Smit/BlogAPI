import api from './api';

// Check if user is authenticated
const isAuthenticated = () => {
  if (typeof window === 'undefined') {
    return false;
  }

  const hasStoredToken = !!localStorage.getItem('jwt_token');
  const hasAuthCookie = document.cookie
    .split(';')
    .some((cookie) => cookie.trim().startsWith('BEARER=') || cookie.trim().startsWith('refresh_token='));

  return hasStoredToken || hasAuthCookie;
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
    if (response.data.token) {
      localStorage.setItem('jwt_token', response.data.token);
      if (response.data.refresh_token) {
        localStorage.setItem('refresh_token', response.data.refresh_token);
      }
      return response.data;
    }
    throw new Error('Ungültige Anmeldedaten');
  } catch (error) {
    console.error('Login-Fehler:', error);
    throw error;
  }
};

// Logout user
const logout = async () => {
  try {
    await api.post('/logout');
  } catch (error) {
    console.error('Logout-Fehler:', error);
  } finally {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('refresh_token');
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