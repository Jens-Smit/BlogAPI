import api from './api';

// Get a new captcha
const getCaptcha = async () => {
  try {
    const response = await api.get('/api/captcha');
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen des Captchas:', error);
    throw error;
  }
};

// Verify captcha
const verifyCaptcha = async (id, text) => {
  try {
    const response = await api.post('/api/captcha/verify', { id, text });
    return response.data;
  } catch (error) {
    console.error('Fehler bei der Captcha-Überprüfung:', error);
    throw error;
  }
};

export { getCaptcha, verifyCaptcha };