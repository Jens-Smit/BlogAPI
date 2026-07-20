import api from './api';

// Send a contact form message
const sendContactMessage = async (contactData) => {
  try {
    const response = await api.post('/contact', contactData);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Senden der Nachricht:', error);
    throw error;
  }
};

// Get all contact messages (for admin)
const getContactMessages = async () => {
  try {
    const response = await api.get('/contact/messages');
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Nachrichten:', error);
    throw error;
  }
};

// Get a single contact message by ID
const getContactMessageById = async (id) => {
  try {
    const response = await api.get(`/contact/messages/${id}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Abrufen der Nachricht:', error);
    throw error;
  }
};

// Mark a contact message as read
const markMessageAsRead = async (id) => {
  try {
    const response = await api.post(`/contact/messages/${id}/read`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Markieren der Nachricht als gelesen:', error);
    throw error;
  }
};

// Delete a contact message
const deleteContactMessage = async (id) => {
  try {
    const response = await api.delete(`/api/contact/messages/${id}`);
    return response.data;
  } catch (error) {
    console.error('Fehler beim Löschen der Nachricht:', error);
    throw error;
  }
};

export { sendContactMessage, getContactMessages, getContactMessageById, markMessageAsRead, deleteContactMessage };